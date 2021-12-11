<?php

namespace App\Http\Controllers;

use App\Libraries\BitcoinApi;
use App\Libraries\PomocnikLiczbowy;
use App\Models\HistoriaSaldaBitkantor;
use App\Models\HistoriaSaldaUzytkownikow;
use App\Models\SaldoBitkantor;
use App\Models\User;
use App\Models\WyplataBtc;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WyplataBtcController extends Controller
{

    public function __construct()
    {
        $this->pomocnikLiczbowy = new PomocnikLiczbowy();
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function wyplacBtc(Request $request)
    {

        $this->authorize('wyplacBtc', WyplataBtc::class);

        $walidator = Validator::make($request->all(), [
            'kwotaWyplatyBtc' => 'required|numeric|min:' . config('app.minimalnaKwotaWyplatyBtc'),
            'adresPortfelaDoWyplaty' => 'required|alpha_num|min:1',
        ]);

        if ($walidator->fails()) {
            if ($walidator->errors()->has('kwotaWyplatyBtc')) {
                return back()->with('blad', 'Nieprawidłowa kwota wypłaty');
            }
            if ($walidator->errors()->has('adresPortfelaDoWyplaty')) {
                return back()->with('blad', 'Nieprawidłowy adres portfela');
            }
        }

        $adresPortfelaDoWyplaty = trim($request->adresPortfelaDoWyplaty);

        $bitcoinApi = new BitcoinApi();

        $czyAdresPortfelaZweryfikowany = $bitcoinApi->weryfikujAdresPortfela($adresPortfelaDoWyplaty);

        if ($czyAdresPortfelaZweryfikowany === false) {
            return back()->with('blad', 'Nieprawidłowy adres portfela');
        } elseif ($czyAdresPortfelaZweryfikowany === null) {
            return back()->with('blad', 'Wystąpił błąd podczas wypłaty BTC');
        }

        $kwotaWyplatyBtc = $this->pomocnikLiczbowy->formatujLiczbe($request->kwotaWyplatyBtc, 8);
        $prowizjaZaWyplateBtc = config('app.prowizjaZaWyplateBtc');

        // Początek transakcji SQL
        DB::beginTransaction();

        try {

            $uzytkownik = User::where('id', $request->user()->id)->lockForUpdate()->firstOrFail();

            if ($uzytkownik->adres_portfela === $adresPortfelaDoWyplaty) {
                DB::rollback();
                return back()->with('blad', 'Nieprawidłowy adres portfela');
            }

            $domyslneParametryLogow = [
                'idUzytkownika' => $uzytkownik->id,
                'kwotaWyplatyBtc' => $kwotaWyplatyBtc,
                'prowizjaBtc' => $prowizjaZaWyplateBtc,
                'adresPortfelaDoWyplaty' => $adresPortfelaDoWyplaty];

            Log::channel('informacje')->withContext($domyslneParametryLogow);
            Log::channel('bledy')->withContext($domyslneParametryLogow);
            Log::channel('wyjatki')->withContext($domyslneParametryLogow);

            $saldoBtc = $uzytkownik->saldo_btc;

            if ($this->pomocnikLiczbowy->czyXMniejszeOdY($saldoBtc, $this->pomocnikLiczbowy->dodajXdoY($kwotaWyplatyBtc, $prowizjaZaWyplateBtc, 8), 8)) {
                DB::rollback();
                return back()->with('blad', 'Twoje saldo jest zbyt niskie');
            }

            $saldoBtcPoZleceniuWyplaty = $this->pomocnikLiczbowy->odejmijYodX($saldoBtc, $this->pomocnikLiczbowy->dodajXdoY($kwotaWyplatyBtc, $prowizjaZaWyplateBtc, 8), 8);

            Log::channel('informacje')->info('Rozpoczęto wypłatę BTC', ['saldoBtcPrzedZleceniemWyplaty' => $saldoBtc, 'saldoBtcPoZleceniuWyplaty' => $saldoBtcPoZleceniuWyplaty]);

            $wyplataBtc = new WyplataBtc();
            $wyplataBtc->uzytkownik()->associate($uzytkownik);
            $wyplataBtc->kwota_btc = $kwotaWyplatyBtc;
            $wyplataBtc->prowizja_btc = $prowizjaZaWyplateBtc;
            $wyplataBtc->adres_portfela_do_wyplaty = $adresPortfelaDoWyplaty;
            $wyplataBtc->status = "Zlecona";
            $zapisanoWyplateBtc = $wyplataBtc->save();

            if (!$zapisanoWyplateBtc) {

                DB::rollback();
                Log::channel('bledy')->error('Błąd podczas zapisywania zlecanej wypłaty BTC');
                return back()->with('blad', 'Wystąpił błąd podczas wypłaty BTC');

            }

            $historiaSaldaUzytkownikaWyplataBtc = new HistoriaSaldaUzytkownikow();
            $historiaSaldaUzytkownikaWyplataBtc->uzytkownik()->associate($uzytkownik);
            $historiaSaldaUzytkownikaWyplataBtc->wyplataBtc()->associate($wyplataBtc);
            $historiaSaldaUzytkownikaWyplataBtc->rodzaj_salda = "BTC";
            $historiaSaldaUzytkownikaWyplataBtc->rodzaj_zmiany_salda = "Odjęcie";
            $historiaSaldaUzytkownikaWyplataBtc->rodzaj_operacji = "Wypłata BTC";
            $historiaSaldaUzytkownikaWyplataBtc->kwota_btc = $this->pomocnikLiczbowy->dodajXdoY($kwotaWyplatyBtc, $prowizjaZaWyplateBtc, 8);
            $historiaSaldaUzytkownikaWyplataBtc->saldo_btc_przed_rozpoczeciem_operacji = $saldoBtc;
            $historiaSaldaUzytkownikaWyplataBtc->saldo_pln_przed_rozpoczeciem_operacji = $uzytkownik->saldo_pln;

            $uzytkownik->saldo_btc = $saldoBtcPoZleceniuWyplaty;
            $zapisanoUzytkownika = $uzytkownik->save();

            if (!$zapisanoUzytkownika) {
                DB::rollback();
                Log::channel('bledy')->error('Błąd podczas zapisywania użytkownika przy zlecaniu wypłaty BTC');
                return back()->with('blad', 'Wystąpił błąd podczas wypłaty BTC');
            }

            $historiaSaldaUzytkownikaWyplataBtc->saldo_btc_po_zakonczeniu_operacji = $uzytkownik->saldo_btc;
            $historiaSaldaUzytkownikaWyplataBtc->saldo_pln_po_zakonczeniu_operacji = $uzytkownik->saldo_pln;

            $zapisanoHistorieSaldaUzytkownika = $historiaSaldaUzytkownikaWyplataBtc->save();

            if (!$zapisanoHistorieSaldaUzytkownika) {
                DB::rollback();
                Log::channel('bledy')->error('Błąd podczas zapisywania historii użytkownika przy wypłacie BTC', ['nowaHistoriaUzytkownika' => $historiaSaldaUzytkownikaWyplataBtc]);
                return back()->with('blad', 'Wystąpił błąd podczas wypłaty BTC');
            }

            DB::commit();
            Log::channel('informacje')->info('Wypłata BTC została zlecona');
            return back()->with('sukces', 'Wypłata BTC została zlecona');
        } catch (Exception $wyjatek) {
            DB::rollback();
            Log::channel('wyjatki')->critical('Wyjątek przy zlecaniu wypłaty BTC', [
                'wyjatek' => $wyjatek]);
            return back()->with('blad', 'Wystąpił błąd podczas wypłaty BTC');
        }
        // Koniec transakcji

    }

    /**
     * Zmienia status wypłaty na "Anulowana" i przywraca środki na saldo
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function anulujWyplateBtc(Request $request, WyplataBtc $wyplataBtc)
    {

        $this->authorize('anulujWyplateBtc', $wyplataBtc);

        DB::beginTransaction();

        try {

            $wyplataBtc = WyplataBtc::where('id', $wyplataBtc->id)->lockForUpdate()->firstOrFail();

            if ($wyplataBtc->status === "Zlecona") {

                $wyplacajacyBtc = $wyplataBtc->uzytkownik()->lockForUpdate()->firstOrFail();
                $kwotaWyplatyBtc = $wyplataBtc->kwota_btc;
                $prowizjaZaWyplateBtc = $wyplataBtc->prowizja_btc;
                $saldoBtcWyplacajacego = $wyplacajacyBtc->saldo_btc;

                $historiaSaldaUzytkownikaAnulowanieWyplatyBtc = new HistoriaSaldaUzytkownikow();
                $historiaSaldaUzytkownikaAnulowanieWyplatyBtc->uzytkownik()->associate($wyplacajacyBtc);
                $historiaSaldaUzytkownikaAnulowanieWyplatyBtc->wyplataBtc()->associate($wyplataBtc);
                $historiaSaldaUzytkownikaAnulowanieWyplatyBtc->rodzaj_salda = "BTC";
                $historiaSaldaUzytkownikaAnulowanieWyplatyBtc->rodzaj_zmiany_salda = "Dodanie";
                $historiaSaldaUzytkownikaAnulowanieWyplatyBtc->rodzaj_operacji = "Anulowanie wypłaty BTC";

                $historiaSaldaUzytkownikaAnulowanieWyplatyBtc->kwota_btc = $this->pomocnikLiczbowy->dodajXdoY($kwotaWyplatyBtc, $prowizjaZaWyplateBtc, 8);
                $historiaSaldaUzytkownikaAnulowanieWyplatyBtc->saldo_btc_przed_rozpoczeciem_operacji = $saldoBtcWyplacajacego;
                $historiaSaldaUzytkownikaAnulowanieWyplatyBtc->saldo_pln_przed_rozpoczeciem_operacji = $wyplacajacyBtc->saldo_pln;

                $domyslneParametryLogow = [
                    'idUzytkownika' => $wyplacajacyBtc->id,
                    'idWyplatyBtc' => $wyplataBtc->id,
                    'kwotaWyplatyBtc' => $kwotaWyplatyBtc,
                    'kwotaProwizjiZaWyplate' => $prowizjaZaWyplateBtc,
                    'saldoBtcWyplacajacegoPrzedAnulowaniemWyplaty' => $saldoBtcWyplacajacego];

                Log::channel('informacje')->withContext($domyslneParametryLogow);
                Log::channel('bledy')->withContext($domyslneParametryLogow);
                Log::channel('wyjatki')->withContext($domyslneParametryLogow);

                Log::channel('informacje')->info('Rozpoczęto anulowanie wypłaty BTC');

                $saldoBtcWyplacajacego = $this->pomocnikLiczbowy->dodajXdoY($saldoBtcWyplacajacego, $this->pomocnikLiczbowy->dodajXdoY($kwotaWyplatyBtc, $prowizjaZaWyplateBtc, 8), 8);
                $wyplacajacyBtc->saldo_btc = $saldoBtcWyplacajacego;

                $wyplataBtc->status = "Anulowana";
                $zapisanoWyplacajacegoBtc = $wyplacajacyBtc->save();

                if (!$zapisanoWyplacajacegoBtc) {
                    // Błąd podczas zapisywania użytkownika
                    DB::rollback();
                    Log::channel('bledy')->error('Błąd podczas zapisywania anulującego wypłatę BTC');
                    return back()->with('blad', 'Wystąpił błąd podczas anulowania wypłaty BTC');
                }

                $zapisanoWyplateBtc = $wyplataBtc->save();

                if (!$zapisanoWyplateBtc) {
                    // Błąd podczas zapisywania anulowanej wypłaty
                    DB::rollback();
                    Log::channel('bledy')->error('Błąd podczas zapisywania anulowanej wypłaty BTC', ['anulowanaWyplataBtc' => $wyplataBtc]);
                    return back()->with('blad', 'Wystąpił błąd podczas anulowania wypłaty BTC');

                }

                $historiaSaldaUzytkownikaAnulowanieWyplatyBtc->saldo_btc_po_zakonczeniu_operacji = $saldoBtcWyplacajacego;
                $historiaSaldaUzytkownikaAnulowanieWyplatyBtc->saldo_pln_po_zakonczeniu_operacji = $wyplacajacyBtc->saldo_pln;

                $zapisanoHistorieSaldaUzytkownika = $historiaSaldaUzytkownikaAnulowanieWyplatyBtc->save();

                if (!$zapisanoHistorieSaldaUzytkownika) {
                    DB::rollback();
                    Log::channel('bledy')->error('Błąd podczas zapisywania historii użytkownika przy anulowaniu wypłaty BTC', ['nowaHistoriaUzytkownika' => $historiaSaldaUzytkownikaAnulowanieWyplatyBtc]);
                    return back()->with('blad', 'Wystąpił błąd podczas anulowania wypłaty BTC');
                }

                DB::commit();
                Log::channel('informacje')->info('Zakończono anulowanie wypłaty BTC', ['saldoKoncoweBtcPoAnulowaniuWyplaty' => $saldoBtcWyplacajacego]);
                return back()->with('sukces', 'Anulowano wypłatę BTC');

            } else {
                DB::rollback();
                return back()->with('blad', 'Wystąpił błąd podczas anulowania wypłaty BTC');
            }

        } catch (Exception $wyjatek) {
            DB::rollback();
            Log::channel('wyjatki')->critical('Wyjątek przy anulowaniu wypłaty BTC', [
                'wyjatek' => $wyjatek]);
            return back()->with('blad', 'Wystąpił błąd podczas anulowania wypłaty BTC');
        }

    }

    // Cronjob
    public function zaksiegujWyplatyBtc()
    {
        // TODO: ulepszyć logi

        DB::beginTransaction();
        try {

            // $domyslneParametryLogow = [
            //     'idUzytkownika' => $uzytkownik->id,
            //     'kwotaWyplatyBtc' => $kwotaWyplatyBtc,
            //     'prowizjaBtc' => $prowizjaZaWyplateBtc,
            //     'adresPortfelaDoWyplaty' => $adresPortfelaDoWyplaty];

            // Log::channel('informacje')->withContext($domyslneParametryLogow);
            // Log::channel('bledy')->withContext($domyslneParametryLogow);
            // Log::channel('wyjatki')->withContext($domyslneParametryLogow);

            // dodaję dwie nowe historie salda i zmiany salda - dodaję prowizję od wypłaty, a odejmuję opłatę za transakcję txid pod warunkiem, że nie została już odjęta wcześniej.

            $saldoBitkantor = SaldoBitkantor::lockForUpdate()->firstOrFail();
            $saldoBitkantorPln = $saldoBitkantor->saldo_pln;
            $saldoBitkantorBtc = $saldoBitkantor->saldo_btc;
            $wyplatyBtcDoZaksiegowania = WyplataBtc::where('status', 'Wysłana')->lockForUpdate()->get();

            foreach ($wyplatyBtcDoZaksiegowania as $wyplataBtc) {

                $transakcjaBlockchainWyplaty = $wyplataBtc->transakcjaBlockchain()->lockForUpdate()->firstOrFail();
                $txidWyplaty = $transakcjaBlockchainWyplaty->txid;
                $liczbaPotwierdzenTransakcji = $transakcjaBlockchainWyplaty->liczba_potwierdzen;

                if ($liczbaPotwierdzenTransakcji < 6) {

                    $informacjeOTransakcji = $bitcoinApi->informacjeOTransakcji($txidWyplaty);
                    if ($informacjeOTransakcji) {
                        $liczbaPotwierdzenTransakcji = $informacjeOTransakcji["confirmations"];

                        if ($liczbaPotwierdzenTransakcji !== $transakcjaBlockchainWyplaty->liczba_potwierdzen) {

                            $transakcjaBlockchainWyplaty->liczba_potwierdzen = $liczbaPotwierdzenTransakcji;

                            $zapisanoTransakcjeBlockchainWyplaty = $transakcjaBlockchainWyplaty->save();

                            if (!$zapisanoTransakcjeBlockchainWyplaty) {
                                DB::rollback();
                                Log::channel('bledy')->error('Błąd podczas zapisywania transakcji blockchain przy księgowaniu wypłaty BTC', ['transakcjaBlockchain' => $transakcjaBlockchainWyplaty]);
                                return false;
                            }

                        }

                        if ($liczbaPotwierdzenTransakcji < 6) {
                            continue;
                        }

                    } else {

                        Log::channel('wyjatki')->critical('Nie uzyskano informacji o transakcji przez Bitcoin API', ["txid" => $txidWyplaty]);
                        continue;

                    }
                }

                $historiaSaldaBitkantorProwizja = new HistoriaSaldaBitkantor();
                $historiaSaldaBitkantorProwizja->rodzaj_salda = "BTC";
                $historiaSaldaBitkantorProwizja->rodzaj_zmiany_salda = "Dodanie";
                $historiaSaldaBitkantorProwizja->saldo_pln_przed_rozpoczeciem_operacji = $saldoBitkantorPln;
                $historiaSaldaBitkantorProwizja->saldo_btc_przed_rozpoczeciem_operacji = $saldoBitkantorBtc;
                $historiaSaldaBitkantorProwizja->rodzaj_operacji = "Prowizja za wypłatę BTC";
                $historiaSaldaBitkantorProwizja->kwota_btc = $wyplataBtc->prowizja_btc;
                $historiaSaldaBitkantorProwizja->saldo_pln_po_zakonczeniu_operacji = $saldoBitkantorPln;

                $saldoBitkantorBtc = $this->pomocnikLiczbowy->dodajXdoY($saldoBitkantorBtc, $wyplataBtc->prowizja_btc, 8);
                $historiaSaldaBitkantorProwizja->saldo_btc_po_zakonczeniu_operacji = $saldoBitkantorBtc;
                $historiaSaldaBitkantorProwizja->wyplataBtc()->associate($wyplataBtc);

                $zapisanoHistoriaSaldaBitkantorProwizja = $historiaSaldaBitkantorProwizja->save();

                if (!$zapisanoHistoriaSaldaBitkantorProwizja) {
                    DB::rollback();
                    Log::channel('bledy')->error('Błąd podczas zapisywania historii salda Bitkantor przy księgowaniu wypłaty BTC (prowizja)', ['nowaHistoriaSaldaBitkantor' => $historiaSaldaBitkantorProwizja]);
                    return false;
                }

                $saldoBitkantor->saldo_btc = $saldoBitkantorBtc;
                $zapisanoSaldoBitkantor = $saldoBitkantor->save();

                if (!$zapisanoSaldoBitkantor) {
                    DB::rollback();
                    Log::channel('bledy')->error('Błąd podczas zapisywania salda Bitkantor przy księgowaniu wypłaty BTC (prowizja)', ['saldoBitkantor' => $saldoBitkantor]);
                    return false;
                }

                if (HistoriaSaldaBitkantor::whereRelation('transakcjaBlockchain', 'id', $wyplataBtc->transakcjaBlockchain->id)->doesntExist()) {

                    $historiaSaldaBitkantorOplata = new HistoriaSaldaBitkantor();
                    $historiaSaldaBitkantorOplata->rodzaj_salda = "BTC";
                    $historiaSaldaBitkantorOplata->rodzaj_zmiany_salda = "Odjęcie";
                    $historiaSaldaBitkantorOplata->saldo_pln_przed_rozpoczeciem_operacji = $saldoBitkantorPln;
                    $historiaSaldaBitkantorOplata->saldo_btc_przed_rozpoczeciem_operacji = $saldoBitkantorBtc;
                    $historiaSaldaBitkantorOplata->rodzaj_operacji = "Opłata za transakcję blockchain";
                    $historiaSaldaBitkantorOplata->kwota_btc = $wyplataBtc->transakcjaBlockchain->oplata_btc;
                    $historiaSaldaBitkantorOplata->saldo_pln_po_zakonczeniu_operacji = $saldoBitkantorPln;

                    $saldoBitkantorBtc = $this->pomocnikLiczbowy->odejmijYodX($saldoBitkantorBtc, $wyplataBtc->transakcjaBlockchain->oplata_btc, 8);
                    $historiaSaldaBitkantorOplata->saldo_btc_po_zakonczeniu_operacji = $saldoBitkantorBtc;
                    $historiaSaldaBitkantorOplata->transakcjaBlockchain()->associate($wyplataBtc->transakcjaBlockchain);

                    $zapisanoHistoriaSaldaBitkantorOplata = $historiaSaldaBitkantorOplata->save();

                    if (!$zapisanoHistoriaSaldaBitkantorOplata) {
                        DB::rollback();
                        Log::channel('bledy')->error('Błąd podczas zapisywania historii salda Bitkantor przy księgowaniu wypłaty BTC (opłata)', ['nowaHistoriaSaldaBitkantor' => $historiaSaldaBitkantorOplata]);
                        return false;
                    }

                    $saldoBitkantor->saldo_btc = $saldoBitkantorBtc;
                    $zapisanoSaldoBitkantor = $saldoBitkantor->save();

                    if (!$zapisanoSaldoBitkantor) {
                        DB::rollback();
                        Log::channel('bledy')->error('Błąd podczas zapisywania salda Bitkantor przy księgowaniu wypłaty BTC (opłata)', ['saldoBitkantor' => $saldoBitkantor]);
                        return false;
                    }

                }
                $wyplataBtc->status = "Zakończona";
                $zapisanoWyplateBtc = $wyplataBtc->save();

                if (!$zapisanoWyplateBtc) {
                    DB::rollback();
                    Log::channel('bledy')->error('Błąd podczas księgowania wypłaty BTC');
                    return false;
                }

            }
            DB::commit();
            Log::channel('informacje')->info('Księgowanie wypłat BTC zostało zakończone pomyślnie');
            return true;
        } catch (Exception $wyjatek) {
            DB::rollback();
            Log::channel('wyjatki')->critical('Wyjątek przy księgowaniu wypłat BTC', [
                'wyjatek' => $wyjatek]);
            return false;
        }

    }

}
