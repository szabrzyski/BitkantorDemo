<?php

namespace App\Http\Controllers;

use App\Libraries\PomocnikLiczbowy;
use App\Models\HistoriaSaldaUzytkownikow;
use App\Models\User;
use App\Models\WyplataPln;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class WyplataPlnController extends Controller
{

    public function __construct()
    {
        $this->pomocnikLiczbowy = new PomocnikLiczbowy();
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function wyplacPln(Request $request)
    {

        $this->authorize('wyplacPln', WyplataPln::class);

        $walidator = Validator::make($request->all(), [
            'kwotaWyplatyPln' => 'required|numeric|min:' . config('app.minimalnaKwotaWyplatyPln'),
            'numerKontaDoWyplaty' => 'required|digits:26',
        ]);

        if ($walidator->fails()) {
            if ($walidator->errors()->has('kwotaWyplatyPln')) {
                return back()->with('blad', 'Nieprawidłowa kwota wypłaty');
            }
            if ($walidator->errors()->has('numerKontaDoWyplaty')) {
                return back()->with('blad', 'Nieprawidłowy numer konta bankowego');
            }
        }

        $numerKontaDoWyplaty = trim($request->numerKontaDoWyplaty);
        $kwotaWyplatyPln = $this->pomocnikLiczbowy->formatujLiczbe($request->kwotaWyplatyPln, 2);
        $prowizjaZaWyplatePln = config('app.prowizjaZaWyplatePln');
        $kontoBankoweBitkantor = Str::replace(' ', '', config('app.kontoBankowe'));

        if ($numerKontaDoWyplaty == $kontoBankoweBitkantor) {
            return back()->with('blad', 'Nieprawidłowy numer konta bankowego');
        }

        // Początek transakcji SQL
        DB::beginTransaction();

        try {

            $uzytkownik = User::where('id', $request->user()->id)->lockForUpdate()->firstOrFail();

            $domyslneParametryLogow = [
                'idUzytkownika' => $uzytkownik->id,
                'kwotaWyplatyPln' => $kwotaWyplatyPln,
                'prowizjaPln' => $prowizjaZaWyplatePln,
                'numerKontaDoWyplaty' => $numerKontaDoWyplaty];

            Log::channel('informacje')->withContext($domyslneParametryLogow);
            Log::channel('bledy')->withContext($domyslneParametryLogow);
            Log::channel('wyjatki')->withContext($domyslneParametryLogow);

            $saldoPln = $uzytkownik->saldo_pln;

            if ($this->pomocnikLiczbowy->czyXMniejszeOdY($saldoPln, $this->pomocnikLiczbowy->dodajXdoY($kwotaWyplatyPln, $prowizjaZaWyplatePln, 2), 2)) {
                DB::rollback();
                return back()->with('blad', 'Twoje saldo jest zbyt niskie');
            }

            $saldoPlnPoZleceniuWyplaty = $this->pomocnikLiczbowy->odejmijYodX($saldoPln, $this->pomocnikLiczbowy->dodajXdoY($kwotaWyplatyPln, $prowizjaZaWyplatePln, 2), 2);

            Log::channel('informacje')->info('Rozpoczęto wypłatę PLN', ['saldoPlnPrzedZleceniemWyplaty' => $saldoPln, 'saldoPlnPoZleceniuWyplaty' => $saldoPlnPoZleceniuWyplaty]);

            $wyplataPln = new WyplataPln();
            $wyplataPln->uzytkownik()->associate($uzytkownik);
            $wyplataPln->kwota_pln = $kwotaWyplatyPln;
            $wyplataPln->prowizja_pln = $prowizjaZaWyplatePln;
            $wyplataPln->konto_bankowe_odbiorcy = $numerKontaDoWyplaty;
            $wyplataPln->status = "Zlecona";
            $zapisanoWyplatePln = $wyplataPln->save();

            if (!$zapisanoWyplatePln) {

                DB::rollback();
                Log::channel('bledy')->error('Błąd podczas zapisywania zlecanej wypłaty PLN');
                return back()->with('blad', 'Wystąpił błąd podczas wypłaty PLN');

            }

            $historiaSaldaUzytkownikaWyplataPln = new HistoriaSaldaUzytkownikow();
            $historiaSaldaUzytkownikaWyplataPln->uzytkownik()->associate($uzytkownik);
            $historiaSaldaUzytkownikaWyplataPln->wyplataPln()->associate($wyplataPln);
            $historiaSaldaUzytkownikaWyplataPln->rodzaj_salda = "PLN";
            $historiaSaldaUzytkownikaWyplataPln->rodzaj_zmiany_salda = "Odjęcie";
            $historiaSaldaUzytkownikaWyplataPln->rodzaj_operacji = "Wypłata PLN";
            $historiaSaldaUzytkownikaWyplataPln->kwota_pln = $this->pomocnikLiczbowy->dodajXdoY($kwotaWyplatyPln, $prowizjaZaWyplatePln, 2);
            $historiaSaldaUzytkownikaWyplataPln->saldo_pln_przed_rozpoczeciem_operacji = $saldoPln;
            $historiaSaldaUzytkownikaWyplataPln->saldo_btc_przed_rozpoczeciem_operacji = $uzytkownik->saldo_btc;

            $uzytkownik->saldo_pln = $saldoPlnPoZleceniuWyplaty;
            $zapisanoUzytkownika = $uzytkownik->save();

            if (!$zapisanoUzytkownika) {
                DB::rollback();
                Log::channel('bledy')->error('Błąd podczas zapisywania użytkownika przy zlecaniu wypłaty PLN');
                return back()->with('blad', 'Wystąpił błąd podczas wypłaty PLN');
            }

            $historiaSaldaUzytkownikaWyplataPln->saldo_pln_po_zakonczeniu_operacji = $uzytkownik->saldo_pln;
            $historiaSaldaUzytkownikaWyplataPln->saldo_btc_po_zakonczeniu_operacji = $uzytkownik->saldo_btc;

            $zapisanoHistorieSaldaUzytkownika = $historiaSaldaUzytkownikaWyplataPln->save();

            if (!$zapisanoHistorieSaldaUzytkownika) {
                DB::rollback();
                Log::channel('bledy')->error('Błąd podczas zapisywania historii użytkownika przy wypłacie PLN', ['nowaHistoriaUzytkownika' => $historiaSaldaUzytkownikaWyplataPln]);
                return back()->with('blad', 'Wystąpił błąd podczas wypłaty BPLN');
            }

            DB::commit();
            Log::channel('informacje')->info('Wypłata PLN została zlecona');
            return back()->with('sukces', 'Wypłata PLN została zlecona');
        } catch (Exception $wyjatek) {
            DB::rollback();
            Log::channel('wyjatki')->critical('Wyjątek przy zlecaniu wypłaty PLN', [
                'wyjatek' => $wyjatek]);
            return back()->with('blad', 'Wystąpił błąd podczas wypłaty PLN');
        }
        // Koniec transakcji

    }

    /**
     * Zmienia status wypłaty na "Anulowana" i przywraca środki na saldo
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function anulujWyplatePln(Request $request, WyplataPln $wyplataPln)
    {

        $this->authorize('anulujWyplatePln', $wyplataPln);

        DB::beginTransaction();

        try {

            $wyplataPln = WyplataPln::where('id', $wyplataPln->id)->lockForUpdate()->firstOrFail();

            if ($wyplataPln->status === "Zlecona") {

                $wyplacajacyPln = $wyplataPln->uzytkownik()->lockForUpdate()->firstOrFail();
                $kwotaWyplatyPln = $wyplataPln->kwota_pln;
                $prowizjaZaWyplatePln = $wyplataPln->prowizja_pln;
                $saldoPlnWyplacajacego = $wyplacajacyPln->saldo_pln;

                $historiaSaldaUzytkownikaAnulowanieWyplatyPln = new HistoriaSaldaUzytkownikow();
                $historiaSaldaUzytkownikaAnulowanieWyplatyPln->uzytkownik()->associate($wyplacajacyPln);
                $historiaSaldaUzytkownikaAnulowanieWyplatyPln->wyplataPln()->associate($wyplataPln);
                $historiaSaldaUzytkownikaAnulowanieWyplatyPln->rodzaj_salda = "PLN";
                $historiaSaldaUzytkownikaAnulowanieWyplatyPln->rodzaj_zmiany_salda = "Dodanie";
                $historiaSaldaUzytkownikaAnulowanieWyplatyPln->rodzaj_operacji = "Anulowanie wypłaty PLN";

                $historiaSaldaUzytkownikaAnulowanieWyplatyPln->kwota_pln = $this->pomocnikLiczbowy->dodajXdoY($kwotaWyplatyPln, $prowizjaZaWyplatePln, 2);
                $historiaSaldaUzytkownikaAnulowanieWyplatyPln->saldo_pln_przed_rozpoczeciem_operacji = $saldoPlnWyplacajacego;
                $historiaSaldaUzytkownikaAnulowanieWyplatyPln->saldo_btc_przed_rozpoczeciem_operacji = $wyplacajacyPln->saldo_btc;

                $domyslneParametryLogow = [
                    'idUzytkownika' => $wyplacajacyPln->id,
                    'idWyplatyPln' => $wyplataPln->id,
                    'kwotaWyplatyPln' => $kwotaWyplatyPln,
                    'kwotaProwizjiZaWyplate' => $prowizjaZaWyplatePln,
                    'saldoPlnWyplacajacegoPrzedAnulowaniemWyplaty' => $saldoPlnWyplacajacego];

                Log::channel('informacje')->withContext($domyslneParametryLogow);
                Log::channel('bledy')->withContext($domyslneParametryLogow);
                Log::channel('wyjatki')->withContext($domyslneParametryLogow);

                Log::channel('informacje')->info('Rozpoczęto anulowanie wypłaty PLN');

                $saldoPlnWyplacajacego = $this->pomocnikLiczbowy->dodajXdoY($saldoPlnWyplacajacego, $this->pomocnikLiczbowy->dodajXdoY($kwotaWyplatyPln, $prowizjaZaWyplatePln, 2), 2);
                $wyplacajacyPln->saldo_pln = $saldoPlnWyplacajacego;

                $wyplataPln->tytul_przelewu = null;
                $wyplataPln->status = "Anulowana";
                $zapisanoWyplacajacegoPln = $wyplacajacyPln->save();

                if (!$zapisanoWyplacajacegoPln) {
                    // Błąd podczas zapisywania użytkownika
                    DB::rollback();
                    Log::channel('bledy')->error('Błąd podczas zapisywania anulującego wypłatę PLN');
                    return back()->with('blad', 'Wystąpił błąd podczas anulowania wypłaty PLN');
                }

                $zapisanoWyplatePln = $wyplataPln->save();

                if (!$zapisanoWyplatePln) {
                    // Błąd podczas zapisywania anulowanej wypłaty
                    DB::rollback();
                    Log::channel('bledy')->error('Błąd podczas zapisywania anulowanej wypłaty PLN', ['anulowanaWyplataPln' => $wyplataPln]);
                    return back()->with('blad', 'Wystąpił błąd podczas anulowania wypłaty PLN');

                }

                $historiaSaldaUzytkownikaAnulowanieWyplatyPln->saldo_pln_po_zakonczeniu_operacji = $saldoPlnWyplacajacego;
                $historiaSaldaUzytkownikaAnulowanieWyplatyPln->saldo_btc_po_zakonczeniu_operacji = $wyplacajacyPln->saldo_btc;

                $zapisanoHistorieSaldaUzytkownika = $historiaSaldaUzytkownikaAnulowanieWyplatyPln->save();

                if (!$zapisanoHistorieSaldaUzytkownika) {
                    DB::rollback();
                    Log::channel('bledy')->error('Błąd podczas zapisywania historii użytkownika przy anulowaniu wypłaty PLN', ['nowaHistoriaUzytkownika' => $historiaSaldaUzytkownikaAnulowanieWyplatyPln]);
                    return back()->with('blad', 'Wystąpił błąd podczas anulowania wypłaty PLN');
                }

                DB::commit();
                Log::channel('informacje')->info('Zakończono anulowanie wypłaty PLN', ['saldoKoncowePlnPoAnulowaniuWyplaty' => $saldoPlnWyplacajacego]);
                return back()->with('sukces', 'Anulowano wypłatę PLN');

            } else {
                DB::rollback();
                return back()->with('blad', 'Wystąpił błąd podczas anulowania wypłaty PLN');
            }

        } catch (Exception $wyjatek) {
            DB::rollback();
            Log::channel('wyjatki')->critical('Wyjątek przy anulowaniu wypłaty PLN', [
                'wyjatek' => $wyjatek]);
            return back()->with('blad', 'Wystąpił błąd podczas anulowania wypłaty PLN');
        }

    }

}
