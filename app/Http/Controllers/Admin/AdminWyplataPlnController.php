<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Libraries\PomocnikLiczbowy;
use App\Models\HistoriaSaldaBitkantor;
use App\Models\HistoriaSaldaUzytkownikow;
use App\Models\SaldoBitkantor;
use App\Models\WyplataPln;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminWyplataPlnController extends Controller
{

    public function __construct()
    {
        $this->pomocnikLiczbowy = new PomocnikLiczbowy();
    }

    public function wyplatyPln(Request $request)
    {

        $wyplatyPln = WyplataPln::with('uzytkownik')->orderByRaw("FIELD(status, 'Zlecona', 'Realizowana', 'Zakończona', 'Anulowana')")->orderBy("updated_at", "DESC")->paginate(15);

        return view('admin.wyplatyPln')->with('wyplatyPln', $wyplatyPln)->with('wyniki', $wyplatyPln);

    }

    /**
     * Zmienia status wypłaty na "Anulowana" i przywraca środki na saldo
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Models\WyplataPln $wyplataPln
     * @return \Illuminate\Http\Response
     */
    public function anulujWyplatePln(Request $request, WyplataPln $wyplataPln)
    {

        $this->authorize('uprawnieniaAdmina', $request->user());

        DB::beginTransaction();

        try {

            $wyplataPln = WyplataPln::where('id', $wyplataPln->id)->lockForUpdate()->firstOrFail();

            if (in_array($wyplataPln->status, ["Zlecona", "Realizowana"])) {

                $wyplacajacyPln = $wyplataPln->uzytkownik()->lockForUpdate()->firstOrFail();
                $kwotaWyplatyPln = $wyplataPln->kwota_pln;
                $saldoPlnWyplacajacego = $wyplacajacyPln->saldo_pln;

                $historiaSaldaUzytkownikaAnulowanieWyplatyPln = new HistoriaSaldaUzytkownikow();
                $historiaSaldaUzytkownikaAnulowanieWyplatyPln->uzytkownik()->associate($wyplacajacyPln);
                $historiaSaldaUzytkownikaAnulowanieWyplatyPln->wyplataPln()->associate($wyplataPln);
                $historiaSaldaUzytkownikaAnulowanieWyplatyPln->rodzaj_salda = "PLN";
                $historiaSaldaUzytkownikaAnulowanieWyplatyPln->rodzaj_zmiany_salda = "Dodanie";
                $historiaSaldaUzytkownikaAnulowanieWyplatyPln->rodzaj_operacji = "Anulowanie wypłaty PLN";
                $historiaSaldaUzytkownikaAnulowanieWyplatyPln->kwota_pln = $kwotaWyplatyPln;
                $historiaSaldaUzytkownikaAnulowanieWyplatyPln->saldo_pln_przed_rozpoczeciem_operacji = $saldoPlnWyplacajacego;
                $historiaSaldaUzytkownikaAnulowanieWyplatyPln->saldo_btc_przed_rozpoczeciem_operacji = $wyplacajacyPln->saldo_btc;

                $domyslneParametryLogow = [
                    'idUzytkownika' => $wyplacajacyPln->id,
                    'idWyplatyPln' => $wyplataPln->id,
                    'kwotaWyplatyPln' => $kwotaWyplatyPln,
                    'saldoPlnWyplacajacegoPrzedAnulowaniemWyplaty' => $saldoPlnWyplacajacego];

                Log::channel('admin')->withContext($domyslneParametryLogow);

                Log::channel('admin')->info('Rozpoczęto anulowanie wypłaty PLN');

                $saldoPlnWyplacajacego = $this->pomocnikLiczbowy->dodajXdoY($saldoPlnWyplacajacego, $kwotaWyplatyPln, 2);
                $wyplacajacyPln->saldo_pln = $saldoPlnWyplacajacego;

                $wyplataPln->tytul_przelewu = null;
                $wyplataPln->status = "Anulowana";
                $zapisanoWyplacajacegoPln = $wyplacajacyPln->save();

                if (!$zapisanoWyplacajacegoPln) {

                    // Błąd podczas zapisywania użytkownika
                    DB::rollback();
                    Log::channel('admin')->error('Błąd podczas zapisywania anulującego wypłatę PLN');
                    return back()->with('blad', 'Wystąpił błąd podczas anulowania wypłaty PLN');
                }

                $zapisanoWyplatePln = $wyplataPln->save();

                if (!$zapisanoWyplatePln) {

                    // Błąd podczas zapisywania anulowanej wypłaty
                    DB::rollback();
                    Log::channel('admin')->error('Błąd podczas zapisywania anulowanej wypłaty PLN', ['anulowanaWyplataPln' => $wyplataPln]);

                    return back()->with('blad', 'Wystąpił błąd podczas anulowania wypłaty PLN');

                }

                $historiaSaldaUzytkownikaAnulowanieWyplatyPln->saldo_pln_po_zakonczeniu_operacji = $saldoPlnWyplacajacego;
                $historiaSaldaUzytkownikaAnulowanieWyplatyPln->saldo_btc_po_zakonczeniu_operacji = $wyplacajacyPln->saldo_btc;

                $zapisanoHistorieSaldaUzytkownika = $historiaSaldaUzytkownikaAnulowanieWyplatyPln->save();

                if (!$zapisanoHistorieSaldaUzytkownika) {
                    DB::rollback();
                    Log::channel('admin')->error('Błąd podczas zapisywania historii użytkownika przy anulowaniu wypłaty PLN', ['nowaHistoriaUzytkownika' => $historiaSaldaUzytkownikaAnulowanieWyplatyPln]);
                    return back()->with('blad', 'Wystąpił błąd podczas anulowania wypłaty PLN');
                }

                DB::commit();
                Log::channel('admin')->info('Zakończono anulowanie wypłaty PLN', ['saldoKoncoweBtcPoAnulowaniuWyplaty' => $saldoPlnWyplacajacego]);
                return back()->with('sukces', 'Anulowano wypłatę PLN');

            } else {
                DB::rollback();
                Log::channel('admin')->error('Próbowano anulować wysłaną wypłatę');
                return back()->with('blad', 'Wystąpił błąd podczas anulowania wypłaty PLN');
            }

        } catch (Exception $wyjatek) {
            DB::rollback();
            Log::channel('admin')->critical('Wyjątek przy anulowaniu wypłaty PLN', [
                'wyjatek' => $wyjatek]);
            return back()->with('blad', 'Wystąpił błąd podczas anulowania wypłaty PLN');
        }

    }

    /**
     * Zmienia status wypłaty na "Realizowana", aby wypłacający nie mógł jej anulować
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Models\WyplataPln $wyplataPln
     * @return \Illuminate\Http\Response
     */
    public function zablokujWyplatePln(Request $request, WyplataPln $wyplataPln)
    {

        $this->authorize('uprawnieniaAdmina', $request->user());

        DB::beginTransaction();

        try {

            $wyplataPln = WyplataPln::where('id', $wyplataPln->id)->lockForUpdate()->firstOrFail();

            if ($wyplataPln->status === "Zlecona") {

                $domyslneParametryLogow = [
                    'idWyplatyPln' => $wyplataPln->id];

                Log::channel('admin')->withContext($domyslneParametryLogow);

                Log::channel('admin')->info('Rozpoczęto blokowanie wypłaty PLN');

                $wyplataPln->tytul_przelewu = config('app.tytulPrzelewuWyplatyPrefiks') . $wyplataPln->id;
                $wyplataPln->status = "Realizowana";
                $zapisanoWyplatePln = $wyplataPln->save();

                if (!$zapisanoWyplatePln) {

                    // Błąd podczas zapisywania anulowanej wypłaty
                    DB::rollback();
                    Log::channel('admin')->error('Błąd podczas zapisywania blokowanej wypłaty PLN', ['blokowanaWyplataPln' => $wyplataPln]);

                    return back()->with('blad', 'Wystąpił błąd podczas blokowania wypłaty PLN');

                }

                DB::commit();
                Log::channel('admin')->info('Zakończono blokowanie wypłaty PLN');
                return back()->with('sukces', 'Zablokowano wypłatę PLN');

            } else {
                DB::rollback();
                Log::channel('admin')->error('Próbowano zablokować wypłatę ze statusem innym niż "Zlecona"');
                return back()->with('blad', 'Wystąpił błąd podczas blokowania wypłaty PLN');
            }

        } catch (Exception $wyjatek) {
            DB::rollback();
            Log::channel('admin')->critical('Wyjątek przy blokowaniu wypłaty PLN', [
                'wyjatek' => $wyjatek]);
            return back()->with('blad', 'Wystąpił błąd podczas blokowania wypłaty PLN');
        }

    }

    /**
     * Zmienia status wypłaty na "Zakończona"
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Models\WyplataPln $wyplataPln
     * @return \Illuminate\Http\Response
     */
    public function zrealizujWyplatePln(Request $request, WyplataPln $wyplataPln)
    {

        $this->authorize('uprawnieniaAdmina', $request->user());

        DB::beginTransaction();

        try {

            $wyplataPln = WyplataPln::where('id', $wyplataPln->id)->lockForUpdate()->firstOrFail();

            if ($wyplataPln->status === "Realizowana") {

                $domyslneParametryLogow = [
                    'idWyplatyPln' => $wyplataPln->id];

                Log::channel('admin')->withContext($domyslneParametryLogow);

                Log::channel('admin')->info('Rozpoczęto realizację wypłaty PLN');

                // Sprawdź, czy wypłata w tym miesiącu jest darmowa

                $obecnyMiesiac = Carbon::now()->month;
                $liczbaDarmowychWyplatWMiesiacu = config('app.liczbaDarmowychWyplatWMiesiacu');
                $oplataZaWyplatePln = config('app.oplataZaWyplatePln');

                $liczbaWczesniejszychWyplatWMiesiacu = WyplataPln::whereMonth('updated_at', $obecnyMiesiac)->where('Status', 'Zakończona')->count();

                if ($liczbaWczesniejszychWyplatWMiesiacu >= 10) {
                    $wyplataPln->oplata_pln = $oplataZaWyplatePln;
                } else {
                    $wyplataPln->oplata_pln = null;
                }

                $wyplataPln->status = "Zakończona";
                $zapisanoWyplatePln = $wyplataPln->save();

                if (!$zapisanoWyplatePln) {

                    // Błąd podczas zapisywania realizowanej wypłaty
                    DB::rollback();
                    Log::channel('admin')->error('Błąd podczas zapisywania realizowanej wypłaty PLN', ['realizowanaWyplataPln' => $wyplataPln]);

                    return back()->with('blad', 'Wystąpił błąd podczas realizowania wypłaty PLN');

                }

                $saldoBitkantor = SaldoBitkantor::lockForUpdate()->firstOrFail();
                $saldoBitkantorPln = $saldoBitkantor->saldo_pln;
                $saldoBitkantorBtc = $saldoBitkantor->saldo_btc;

                $historiaSaldaBitkantorProwizja = new HistoriaSaldaBitkantor();
                $historiaSaldaBitkantorProwizja->rodzaj_salda = "PLN";
                $historiaSaldaBitkantorProwizja->rodzaj_zmiany_salda = "Dodanie";
                $historiaSaldaBitkantorProwizja->saldo_pln_przed_rozpoczeciem_operacji = $saldoBitkantorPln;
                $historiaSaldaBitkantorProwizja->saldo_btc_przed_rozpoczeciem_operacji = $saldoBitkantorBtc;
                $historiaSaldaBitkantorProwizja->rodzaj_operacji = "Prowizja za wypłatę PLN";
                $historiaSaldaBitkantorProwizja->kwota_pln = $wyplataPln->prowizja_pln;
                $historiaSaldaBitkantorProwizja->saldo_btc_po_zakonczeniu_operacji = $saldoBitkantorBtc;

                $saldoBitkantorPln = $this->pomocnikLiczbowy->dodajXdoY($saldoBitkantorPln, $wyplataPln->prowizja_pln, 2);
                $historiaSaldaBitkantorProwizja->saldo_pln_po_zakonczeniu_operacji = $saldoBitkantorPln;
                $historiaSaldaBitkantorProwizja->wyplataPln()->associate($wyplataPln);

                $zapisanoHistoriaSaldaBitkantorProwizja = $historiaSaldaBitkantorProwizja->save();

                if (!$zapisanoHistoriaSaldaBitkantorProwizja) {
                    DB::rollback();
                    Log::channel('bledy')->error('Błąd podczas zapisywania historii salda Bitkantor przy księgowaniu wypłaty PLN (prowizja)', ['nowaHistoriaSaldaBitkantor' => $historiaSaldaBitkantorProwizja]);
                    return false;
                }

                $saldoBitkantor->saldo_pln = $saldoBitkantorPln;
                $zapisanoSaldoBitkantor = $saldoBitkantor->save();

                if (!$zapisanoSaldoBitkantor) {
                    DB::rollback();
                    Log::channel('bledy')->error('Błąd podczas zapisywania salda Bitkantor przy księgowaniu wypłaty PLN (prowizja)', ['saldoBitkantor' => $saldoBitkantor]);
                    return false;
                }

                if ($wyplataPln->oplata_pln) {

                    $historiaSaldaBitkantorOplata = new HistoriaSaldaBitkantor();
                    $historiaSaldaBitkantorOplata->rodzaj_salda = "PLN";
                    $historiaSaldaBitkantorOplata->rodzaj_zmiany_salda = "Odjęcie";
                    $historiaSaldaBitkantorOplata->saldo_pln_przed_rozpoczeciem_operacji = $saldoBitkantorPln;
                    $historiaSaldaBitkantorOplata->saldo_btc_przed_rozpoczeciem_operacji = $saldoBitkantorBtc;
                    $historiaSaldaBitkantorOplata->rodzaj_operacji = "Opłata za wypłatę PLN";
                    $historiaSaldaBitkantorOplata->kwota_pln = $wyplataPln->oplata_pln;
                    $historiaSaldaBitkantorOplata->saldo_btc_po_zakonczeniu_operacji = $saldoBitkantorBtc;

                    $saldoBitkantorPln = $this->pomocnikLiczbowy->odejmijYodX($saldoBitkantorPln, $wyplataPln->oplata_pln, 2);
                    $historiaSaldaBitkantorOplata->saldo_pln_po_zakonczeniu_operacji = $saldoBitkantorPln;
                    $historiaSaldaBitkantorOplata->wyplataPln()->associate($wyplataPln);

                    $zapisanoHistoriaSaldaBitkantorOplata = $historiaSaldaBitkantorOplata->save();

                    if (!$zapisanoHistoriaSaldaBitkantorOplata) {
                        DB::rollback();
                        Log::channel('bledy')->error('Błąd podczas zapisywania historii salda Bitkantor przy księgowaniu wypłaty PLN (opłata)', ['nowaHistoriaSaldaBitkantor' => $historiaSaldaBitkantorOplata]);
                        return false;
                    }

                    $saldoBitkantor->saldo_pln = $saldoBitkantorPln;
                    $zapisanoSaldoBitkantor = $saldoBitkantor->save();

                    if (!$zapisanoSaldoBitkantor) {
                        DB::rollback();
                        Log::channel('bledy')->error('Błąd podczas zapisywania salda Bitkantor przy księgowaniu wypłaty PLN (opłata)', ['saldoBitkantor' => $saldoBitkantor]);
                        return false;
                    }

                }

                DB::commit();
                Log::channel('admin')->info('Wypłata PLN została zakończona');
                return back()->with('sukces', 'Wypłata PLN została zakończona');

            } else {
                DB::rollback();
                Log::channel('admin')->error('Próbowano zrealizować wypłatę ze statusem innym niż "Realizowana"');
                return back()->with('blad', 'Wystąpił błąd podczas realizowania wypłaty PLN');
            }

        } catch (Exception $wyjatek) {
            DB::rollback();
            Log::channel('admin')->critical('Wyjątek przy realizowaniu wypłaty PLN', [
                'wyjatek' => $wyjatek]);
            return back()->with('blad', 'Wystąpił błąd podczas realizowania wypłaty PLN');
        }

    }

}
