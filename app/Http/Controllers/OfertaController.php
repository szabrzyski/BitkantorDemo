<?php

namespace App\Http\Controllers;

use App\Libraries\PomocnikLiczbowy;
use App\Models\HistoriaSaldaBitkantor;
use App\Models\HistoriaSaldaUzytkownikow;
use App\Models\Oferta;
use App\Models\SaldoBitkantor;
use App\Models\Transakcja;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OfertaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function __construct()
    {
        $this->pomocnikLiczbowy = new PomocnikLiczbowy();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function wystawOferte(Request $request)
    {
        $this->authorize('wystawOferte', Oferta::class);

        $walidator = Validator::make($request->all(), [
            'typ' => 'required|string|in:zakup,sprzedaż',
            'kwotaBtc' => 'required|numeric|min:' . config('app.minimalnaKwotaBtcWOfercie'),
            'kurs' => 'required|numeric|min:' . config('app.minimalnyKursBtcWOfercie'),
        ]);

        if ($walidator->fails()) {
            return response()->json(['komunikat' => 'Wprowadzono nieprawidłową kwotę w ofercie'], 521);
        }
        $typOferty = $request->typ;
        $kursRequest = $request->kurs;
        $kwotaBtcRequest = $request->kwotaBtc;
        $kurs = $this->pomocnikLiczbowy->zaokraglWDolPoPrzecinku($kursRequest, 2);
        $kwotaBtc = $this->pomocnikLiczbowy->zaokraglWDolPoPrzecinku($kwotaBtcRequest, 8);
        $kwotaPln = $this->pomocnikLiczbowy->zaokraglWDolPoPrzecinku($kurs * $kwotaBtc, 2);

        if ($this->pomocnikLiczbowy->czyXWiekszeLubRowneY($kurs, config('app.minimalnyKursBtcWOfercie'), 2) && $this->pomocnikLiczbowy->czyXWiekszeLubRowneY($kwotaBtc, config('app.minimalnaKwotaBtcWOfercie'), 8) && $this->pomocnikLiczbowy->czyXWiekszeLubRowneY($kwotaPln, config('app.minimalnaWartoscPlnWOfercie'), 2)) {

            $globalnaProwizjaTransakcyjnaProcent = config('app.prowizjaTransakcyjnaProcent');

            // Początek transakcji SQL
            DB::beginTransaction();

            try {

                $uzytkownik = User::where('id', $request->user()->id)->lockForUpdate()->firstOrFail();

                $domyslneParametryLogow = [
                    'idUzytkownika' => $uzytkownik->id,
                    'typOferty' => $typOferty,
                    'kurs' => $kurs,
                    'kursRequest' => $kursRequest,
                    'kwotaBtc' => $kwotaBtc,
                    'kwotaBtcRequest' => $kwotaBtcRequest,
                    'kwotaPln' => $kwotaPln,
                ];

                Log::channel('informacje')->withContext($domyslneParametryLogow);
                Log::channel('bledy')->withContext($domyslneParametryLogow);
                Log::channel('wyjatki')->withContext($domyslneParametryLogow);

                $saldoPln = $uzytkownik->saldo_pln;
                $saldoBtc = $uzytkownik->saldo_btc;
                $pozostalaKwotaBtc = $kwotaBtc;

                $nowaOferta = new Oferta;
                $nowaOferta->typ = Str::ucfirst($typOferty);
                $nowaOferta->kwota_pln = $kwotaPln;
                $nowaOferta->kwota_btc = $kwotaBtc;
                $nowaOferta->kurs = $kurs;
                $nowaOferta->tryb_rozliczania = $uzytkownik->tryb_rozliczania;
                $saldoPlnWystawiajacegoPrzedWystawieniemOferty = $saldoPln;
                $saldoBtcWystawiajacegoPrzedWystawieniemOferty = $saldoBtc;

                // Prowizja wystawiającego ofertę

                if ($uzytkownik->tryb_rozliczania === "Prowizja") {
                    // Wystawiający ofertę rozlicza się prowizją
                    $prowizjaProcent = $uzytkownik->prowizjaProcent();
                    $nowaOferta->prowizja_procent = $prowizjaProcent;
                }

                Log::channel('informacje')->info('Rozpoczęto dodawanie oferty', ['saldoPoczatkowePLN' => $saldoPln, 'saldoPoczatkoweBTC' => $saldoBtc]);
                // Krok 1a. Jeśli zakup BTC, to odejmij od salda PLN wydawaną kwotę
                if ($typOferty === "zakup") {

                    if ($this->pomocnikLiczbowy->czyXWiekszeLubRowneY($saldoPln, $kwotaPln, 2)) {
                        // Wyliczenie pełnej prowizji BTC wystawiającego do zapłaty
                        if ($uzytkownik->tryb_rozliczania === "Prowizja") {
                            $prowizjaBitkantorBtcPrzedZaokragleniem = $this->pomocnikLiczbowy->podzielXprzezY($this->pomocnikLiczbowy->pomnozXprzezY($kwotaBtc, $prowizjaProcent, 10), 100, 12);
                            $prowizjaBitkantorBtc = $this->pomocnikLiczbowy->zaokraglWGorePoPrzecinku($prowizjaBitkantorBtcPrzedZaokragleniem, 8);
                            $nowaOferta->prowizja_bitkantor_btc = $prowizjaBitkantorBtc;
                            $zaplaconaProwizjaBtc = 0.00000000;
                        }
                        $zyskanaKwotaBtc = 0.00000000;

                        $saldoPln = $this->pomocnikLiczbowy->odejmijYodX($saldoPln, $kwotaPln, 2);
                        // Pobierz wszystkie aktywne oferty nienależące do bieżącego użytkownika, jeśli saldo jest wystaczające
                        $aktywneKontroferty = Oferta::where('status', 'Aktywna')->where('typ', 'Sprzedaż')->where('kurs', '<=', $kurs)->where('wystawiajacy_id', '!=', $uzytkownik->id)->lockForUpdate()->orderBy('kurs', 'ASC')->get();
                    } else {
                        DB::rollback();
                        Log::channel('informacje')->info('Brak środków PLN na koncie przy składaniu oferty');
                        return response()->json(['komunikat' => 'Nie posiadasz wystarczających środków na koncie'], 521);
                    }
                } elseif ($typOferty === "sprzedaż") {

                    // Krok 1b. Jeśli sprzedaż BTC, to odejmij od salda BTC odpowiednią ilość BTC
                    if ($this->pomocnikLiczbowy->czyXWiekszeLubRowneY($saldoBtc, $kwotaBtc, 8)) {
                        // Wyliczenie pełnej prowizji PLN wystawiającego do zapłaty
                        if ($uzytkownik->tryb_rozliczania === "Prowizja") {
                            $prowizjaBitkantorPlnPrzedZaokragleniem = $this->pomocnikLiczbowy->podzielXprzezY($this->pomocnikLiczbowy->pomnozXprzezY($kwotaPln, $prowizjaProcent, 4), 100, 6);
                            $prowizjaBitkantorPln = $this->pomocnikLiczbowy->zaokraglWGorePoPrzecinku($prowizjaBitkantorPlnPrzedZaokragleniem, 2);
                            $nowaOferta->prowizja_bitkantor_pln = $prowizjaBitkantorPln;
                            $zaplaconaProwizjaPln = 0.00;
                        }

                        $zyskanaKwotaPln = 0.00;
                        $saldoBtc = $this->pomocnikLiczbowy->odejmijYodX($saldoBtc, $kwotaBtc, 8);
                        $aktywneKontroferty = Oferta::where('status', 'Aktywna')->where('typ', 'Zakup')->where('kurs', '>=', $kurs)->where('wystawiajacy_id', '!=', $uzytkownik->id)->lockForUpdate()->orderBy('kurs', 'DESC')->get();
                    } else {
                        DB::rollback();
                        Log::channel('informacje')->info('Brak środków BTC na koncie przy składaniu oferty');
                        return response()->json(['komunikat' => 'Nie posiadasz wystarczających środków na koncie'], 521);
                    }
                }

                $transakcje = array();

                // Krok 2. Znaleziono kontroferty, dla każdej z nich wykonuj:
                $historieSaldaUzytkownikaTransakcje = array();
                foreach ($aktywneKontroferty as $kontroferta) {

                    $pozostalaKwotaBtcWKontrofercie = $kontroferta->pozostala_kwota_btc;
                    $kursKontroferty = $kontroferta->kurs;
                    $wystawiajacyKontroferte = $kontroferta->wystawiajacy()->lockForUpdate()->firstOrFail();

                    $saldoPlnWystawiajacegoKontroferte = $wystawiajacyKontroferte->saldo_pln;
                    $saldoBtcWystawiajacegoKontroferte = $wystawiajacyKontroferte->saldo_btc;

                    $nowaTransakcja = new Transakcja;
                    $nowaTransakcja->wystawiajacy()->associate($uzytkownik);
                    $nowaTransakcja->przyjmujacy()->associate($wystawiajacyKontroferte);
                    $nowaTransakcja->przyjmujacaOferta()->associate($kontroferta);
                    $nowaTransakcja->typ_oferty_wystawianej = $nowaOferta->typ;
                    $nowaTransakcja->typ_oferty_przyjmujacej = $kontroferta->typ;

                    $historiaSaldaUzytkownikaTransakcjaWystawiajacy = new HistoriaSaldaUzytkownikow();
                    $historiaSaldaUzytkownikaTransakcjaPrzyjmujacy = new HistoriaSaldaUzytkownikow();
                    $historiaSaldaUzytkownikaTransakcjaWystawiajacy->uzytkownik()->associate($uzytkownik);
                    $historiaSaldaUzytkownikaTransakcjaPrzyjmujacy->uzytkownik()->associate($wystawiajacyKontroferte);
                    $historiaSaldaUzytkownikaTransakcjaWystawiajacy->rodzaj_zmiany_salda = "Dodanie";
                    $historiaSaldaUzytkownikaTransakcjaPrzyjmujacy->rodzaj_zmiany_salda = "Dodanie";

                    if ($nowaOferta->typ === "Zakup") {
                        $historiaSaldaUzytkownikaTransakcjaWystawiajacy->rodzaj_operacji = "Transakcja zakupu BTC";
                        $historiaSaldaUzytkownikaTransakcjaWystawiajacy->rodzaj_salda = "BTC";
                        $historiaSaldaUzytkownikaTransakcjaPrzyjmujacy->rodzaj_operacji = "Transakcja sprzedaży BTC";
                        $historiaSaldaUzytkownikaTransakcjaPrzyjmujacy->rodzaj_salda = "PLN";
                    } elseif ($nowaOferta->typ === "Sprzedaż") {
                        $historiaSaldaUzytkownikaTransakcjaWystawiajacy->rodzaj_operacji = "Transakcja sprzedaży BTC";
                        $historiaSaldaUzytkownikaTransakcjaWystawiajacy->rodzaj_salda = "PLN";
                        $historiaSaldaUzytkownikaTransakcjaPrzyjmujacy->rodzaj_operacji = "Transakcja zakupu BTC";
                        $historiaSaldaUzytkownikaTransakcjaPrzyjmujacy->rodzaj_salda = "BTC";
                    }
                    $historiaSaldaUzytkownikaTransakcjaWystawiajacy->transakcja()->associate($nowaTransakcja);
                    $historiaSaldaUzytkownikaTransakcjaPrzyjmujacy->transakcja()->associate($nowaTransakcja);
                    $historiaSaldaUzytkownikaTransakcjaWystawiajacy->saldo_pln_przed_rozpoczeciem_operacji = $saldoPln;
                    $historiaSaldaUzytkownikaTransakcjaPrzyjmujacy->saldo_pln_przed_rozpoczeciem_operacji = $saldoPlnWystawiajacegoKontroferte;
                    $historiaSaldaUzytkownikaTransakcjaWystawiajacy->saldo_btc_przed_rozpoczeciem_operacji = $saldoBtc;
                    $historiaSaldaUzytkownikaTransakcjaPrzyjmujacy->saldo_btc_przed_rozpoczeciem_operacji = $saldoBtcWystawiajacegoKontroferte;

                    Log::channel('informacje')->info('Rozpoczęto transakcję z kontrofertą', ['idKontroferty' => $kontroferta->id, 'saldoPoczatkowePLNWystawiajacegoKontroferte' => $saldoPlnWystawiajacegoKontroferte, 'saldoPoczatkoweBTCWystawiajacegoKontroferte' => $saldoBtcWystawiajacegoKontroferte, 'kursKontroferty' => $kursKontroferty, 'pozostalaKwotaBtcWKontrofercie' => $pozostalaKwotaBtcWKontrofercie]);

                    if ($this->pomocnikLiczbowy->czyXWiekszeOdY($pozostalaKwotaBtcWKontrofercie, $pozostalaKwotaBtc, 8)) {
                        // Jeśli pozostała kwota BTC kontroferty jest większa od pozostałej kwoty nowej oferty, to pozostała kwota BTC nowej oferty jest zerowana, a pozostała kwota BTC kontroferty zostaje pomniejszona o pozostałą kwotę BTC nowej oferty
                        $przelanaKwotaBtc = $pozostalaKwotaBtc;

                        $pozostalaKwotaBtcWKontrofercie = $this->pomocnikLiczbowy->odejmijYodX($pozostalaKwotaBtcWKontrofercie, $przelanaKwotaBtc, 8);
                        $pozostalaKwotaBtc = 0;
                    } else {
                        // Jeśli pozostała kwota BTC w kontrofercie jest mniejsza lub równa pozostałej kwocie nowej oferty, to pozostała kwota BTC kontroferty jest zerowana i kontroferta zostaje zakończona, a pozostała kwota BTC oferty zostaje pomniejszona o pozostałą kwotę BTC kontroferty
                        $przelanaKwotaBtc = $pozostalaKwotaBtcWKontrofercie;
                        $pozostalaKwotaBtc = $this->pomocnikLiczbowy->odejmijYodX($pozostalaKwotaBtc, $przelanaKwotaBtc, 8);
                        $pozostalaKwotaBtcWKontrofercie = 0;
                        $kontroferta->status = 'Zakończona';
                    }
                    $kontroferta->pozostala_kwota_btc = $pozostalaKwotaBtcWKontrofercie;

                    // Sprawdzenie trybu rozliczania kontroferty i pobranie prowizji
                    $trybRozliczeniaKontroferty = $kontroferta->tryb_rozliczania;
                    if ($trybRozliczeniaKontroferty === "Prowizja") {
                        if (!is_null($uzytkownik->osobista_prowizja_procent)) {
                            $prowizjaProcentKontroferty = $uzytkownik->osobista_prowizja_procent;
                        } else {
                            $prowizjaProcentKontroferty = $globalnaProwizjaTransakcyjnaProcent;
                        }
                    }
                    $przelanaKwotaPln = $this->pomocnikLiczbowy->zaokraglWDolPoPrzecinku($this->pomocnikLiczbowy->pomnozXprzezY($kursKontroferty, $przelanaKwotaBtc, 10), 2);
                    // Jeśli zlecono zakup BTC, podnieś saldo PLN właściciela kontroferty
                    if ($typOferty === "zakup") {

                        // Wyliczenie prowizji BTC wystawiającego ofertę
                        if ($uzytkownik->tryb_rozliczania === "Prowizja") {

                            $zaplaconaProwizjaBtcWTransakcjiPrzedZaokragleniem = $this->pomocnikLiczbowy->podzielXprzezY($this->pomocnikLiczbowy->pomnozXprzezY($przelanaKwotaBtc, $prowizjaProcent, 10), 100, 12);
                            $zaplaconaProwizjaBtcWTransakcji = $this->pomocnikLiczbowy->zaokraglWGorePoPrzecinku($zaplaconaProwizjaBtcWTransakcjiPrzedZaokragleniem, 8);

                            $zaplaconaProwizjaBtc = $this->pomocnikLiczbowy->dodajXdoY($zaplaconaProwizjaBtc, $zaplaconaProwizjaBtcWTransakcji, 8);
                            $zyskanaKwotaBtc = $this->pomocnikLiczbowy->odejmijYodX($przelanaKwotaBtc, $zaplaconaProwizjaBtcWTransakcji, 8);
                        } else {
                            $zyskanaKwotaBtc = $przelanaKwotaBtc;
                        }
                        // $saldoBtcPrzedZakonczeniemOferty = $saldoBtc;
                        $saldoBtc = $saldoBtc + $zyskanaKwotaBtc;
                        $historiaSaldaUzytkownikaTransakcjaWystawiajacy->kwota_btc = $zyskanaKwotaBtc;

                        // Wyliczenie prowizji PLN właściciela kontroferty
                        if ($trybRozliczeniaKontroferty === "Prowizja") {
                            $zaplaconaProwizjaPlnWKontroferciePrzedZaokragleniem = $this->pomocnikLiczbowy->podzielXprzezY($this->pomocnikLiczbowy->pomnozXprzezY($prowizjaProcentKontroferty, $przelanaKwotaPln, 4), 100, 6);
                            $zaplaconaProwizjaPlnWKontrofercie = $this->pomocnikLiczbowy->zaokraglWGorePoPrzecinku($zaplaconaProwizjaPlnWKontroferciePrzedZaokragleniem, 2);

                            $zyskanaKwotaPlnWlascicielaKontroferty = $this->pomocnikLiczbowy->odejmijYodX($przelanaKwotaPln, $zaplaconaProwizjaPlnWKontrofercie, 2);
                        } else {
                            $zyskanaKwotaPlnWlascicielaKontroferty = $przelanaKwotaPln;
                        }

                        $saldoPlnWystawiajacegoKontroferte = $this->pomocnikLiczbowy->dodajXdoY($saldoPlnWystawiajacegoKontroferte, $zyskanaKwotaPlnWlascicielaKontroferty, 2);
                        $wystawiajacyKontroferte->saldo_pln = $saldoPlnWystawiajacegoKontroferte;
                        $historiaSaldaUzytkownikaTransakcjaPrzyjmujacy->kwota_pln = $zyskanaKwotaPlnWlascicielaKontroferty;

                        // Jeśli zlecono sprzedaż BTC, podnieś saldo BTC właściciela kontroferty
                    } elseif ($typOferty === "sprzedaż") {

                        // Wyliczenie prowizji PLN wystawiającego ofertę
                        if ($uzytkownik->tryb_rozliczania === "Prowizja") {
                            $zaplaconaProwizjaPlnWTransakcjiPrzedZaokragleniem = $this->pomocnikLiczbowy->podzielXprzezY($this->pomocnikLiczbowy->pomnozXprzezY($przelanaKwotaPln, $prowizjaProcent, 4), 100, 6);
                            $zaplaconaProwizjaPlnWTransakcji = $this->pomocnikLiczbowy->zaokraglWGorePoPrzecinku($zaplaconaProwizjaPlnWTransakcjiPrzedZaokragleniem, 2);
                            $zyskanaKwotaPln = $this->pomocnikLiczbowy->odejmijYodX($przelanaKwotaPln, $zaplaconaProwizjaPlnWTransakcji, 2);

                            $zaplaconaProwizjaPln = $this->pomocnikLiczbowy->dodajXdoY($zaplaconaProwizjaPln, $zaplaconaProwizjaPlnWTransakcji, 2);
                        } else {
                            $zyskanaKwotaPln = $przelanaKwotaPln;
                        }

                        $saldoPln = $this->pomocnikLiczbowy->dodajXdoY($saldoPln, $zyskanaKwotaPln, 2);
                        $historiaSaldaUzytkownikaTransakcjaWystawiajacy->kwota_pln = $zyskanaKwotaPln;

                        // Wyliczenie prowizji BTC właściciela kontroferty
                        if ($trybRozliczeniaKontroferty === "Prowizja") {
                            $zaplaconaProwizjaBtcWKontroferciePrzedZaokragleniem = $this->pomocnikLiczbowy->podzielXprzezY($this->pomocnikLiczbowy->pomnozXprzezY($prowizjaProcentKontroferty, $przelanaKwotaBtc, 10), 100, 12);
                            $zaplaconaProwizjaBtcWKontrofercie = $this->pomocnikLiczbowy->zaokraglWGorePoPrzecinku($zaplaconaProwizjaBtcWKontroferciePrzedZaokragleniem, 8);

                            $zyskanaKwotaBtcWlascicielaKontroferty = $this->pomocnikLiczbowy->odejmijYodX($przelanaKwotaBtc, $zaplaconaProwizjaBtcWKontrofercie, 8);
                        } else {
                            $zyskanaKwotaBtcWlascicielaKontroferty = $przelanaKwotaBtc;
                        }

                        $saldoBtcWystawiajacegoKontroferte = $this->pomocnikLiczbowy->dodajXdoY($saldoBtcWystawiajacegoKontroferte, $zyskanaKwotaBtcWlascicielaKontroferty, 8);
                        $wystawiajacyKontroferte->saldo_btc = $saldoBtcWystawiajacegoKontroferte;
                        $historiaSaldaUzytkownikaTransakcjaPrzyjmujacy->kwota_btc = $zyskanaKwotaBtcWlascicielaKontroferty;
                    }

                    // Krok 3. Zapisz kontrofertę
                    $zapisanoWystawiajacegoKontroferte = $wystawiajacyKontroferte->save();
                    if (!$zapisanoWystawiajacegoKontroferte) {
                        DB::rollback();
                        Log::channel('bledy')->error('Błąd podczas zapisywania wystawiającego kontrofertę', ['wystawiajacyKontroferte' => $wystawiajacyKontroferte]);
                        return response()->json(['komunikat' => 'Wystąpił błąd podczas dodawania oferty'], 521);
                    }

                    $zapisanoKontroferte = $kontroferta->save();

                    if (!$zapisanoKontroferte) {
                        DB::rollback();
                        Log::channel('bledy')->error('Błąd podczas zapisywania kontroferty', ['kontroferta' => $kontroferta]);
                        return response()->json(['komunikat' => 'Wystąpił błąd podczas dodawania oferty'], 521);
                    }

                    // Krok 4. Utwórz nową transakcję

                    $nowaTransakcja->kwota_btc = $przelanaKwotaBtc;
                    $nowaTransakcja->kwota_pln = $przelanaKwotaPln;
                    $nowaTransakcja->kurs = $kursKontroferty;
                    $nowaTransakcja->tryb_rozliczania_wystawiajacego = $uzytkownik->tryb_rozliczania;
                    $nowaTransakcja->tryb_rozliczania_przyjmujacego = $wystawiajacyKontroferte->tryb_rozliczania;

                    if ($uzytkownik->tryb_rozliczania === "Prowizja" || $wystawiajacyKontroferte->tryb_rozliczania === "Prowizja") {

                        $prowizjaBitkantorPln = 0.00;
                        $prowizjaBitkantorBtc = 0.00000000;
                        $prowizjaBitkantorPlnIstnieje = false;
                        $prowizjaBitkantorBtcIstnieje = false;

                        if ($uzytkownik->tryb_rozliczania === "Prowizja") {
                            $nowaTransakcja->prowizja_wystawiajacego_procent = $prowizjaProcent;
                            if ($typOferty === "zakup") {
                                $nowaTransakcja->prowizja_wystawiajacego_btc = $zaplaconaProwizjaBtcWTransakcji;

                                $prowizjaBitkantorBtc = $this->pomocnikLiczbowy->dodajXdoY($prowizjaBitkantorBtc, $zaplaconaProwizjaBtcWTransakcji, 8);
                                $prowizjaBitkantorBtcIstnieje = true;
                            } elseif ($typOferty === "sprzedaż") {
                                $nowaTransakcja->prowizja_wystawiajacego_pln = $zaplaconaProwizjaPlnWTransakcji;

                                $prowizjaBitkantorPln = $this->pomocnikLiczbowy->dodajXdoY($prowizjaBitkantorPln, $zaplaconaProwizjaPlnWTransakcji, 2);
                                $prowizjaBitkantorPlnIstnieje = true;
                            }
                        }
                        if ($wystawiajacyKontroferte->tryb_rozliczania === "Prowizja") {
                            $nowaTransakcja->prowizja_przyjmujacego_procent = $prowizjaProcentKontroferty;
                            if ($typOferty === "zakup") {
                                $nowaTransakcja->prowizja_przyjmujacego_pln = $zaplaconaProwizjaPlnWKontrofercie;

                                $prowizjaBitkantorPln = $this->pomocnikLiczbowy->dodajXdoY($prowizjaBitkantorPln, $zaplaconaProwizjaPlnWKontrofercie, 2);
                                $prowizjaBitkantorPlnIstnieje = true;
                            } elseif ($typOferty === "sprzedaż") {
                                $nowaTransakcja->prowizja_przyjmujacego_btc = $zaplaconaProwizjaBtcWKontrofercie;

                                $prowizjaBitkantorBtc = $this->pomocnikLiczbowy->dodajXdoY($prowizjaBitkantorBtc, $zaplaconaProwizjaBtcWKontrofercie, 8);
                                $prowizjaBitkantorBtcIstnieje = true;
                            }
                        }

                        if ($prowizjaBitkantorPlnIstnieje) {
                            $nowaTransakcja->prowizja_bitkantor_pln = $prowizjaBitkantorPln;
                        }
                        if ($prowizjaBitkantorBtcIstnieje) {
                            $nowaTransakcja->prowizja_bitkantor_btc = $prowizjaBitkantorBtc;
                        }
                    }
                    $historiaSaldaUzytkownikaTransakcjaWystawiajacy->saldo_pln_po_zakonczeniu_operacji = $saldoPln;
                    $historiaSaldaUzytkownikaTransakcjaPrzyjmujacy->saldo_pln_po_zakonczeniu_operacji = $saldoPlnWystawiajacegoKontroferte;
                    $historiaSaldaUzytkownikaTransakcjaWystawiajacy->saldo_btc_po_zakonczeniu_operacji = $saldoBtc;
                    $historiaSaldaUzytkownikaTransakcjaPrzyjmujacy->saldo_btc_po_zakonczeniu_operacji = $saldoBtcWystawiajacegoKontroferte;

                    $transakcje[] = $nowaTransakcja;
                    $historieSaldaUzytkownikaTransakcje[] = $historiaSaldaUzytkownikaTransakcjaWystawiajacy;
                    $historieSaldaUzytkownikaTransakcje[] = $historiaSaldaUzytkownikaTransakcjaPrzyjmujacy;

                    Log::channel('informacje')->info('Zakończono transakcję z kontrofertą', ['idKontroferty' => $kontroferta->id, 'saldoKoncowePLNWystawiajacegoKontroferte' => $saldoPlnWystawiajacegoKontroferte, 'saldoKoncoweBTCWystawiajacegoKontroferte' => $saldoBtcWystawiajacegoKontroferte, 'pozostalaKwotaBtcWKontrofercie' => $pozostalaKwotaBtcWKontrofercie]);

                    // Krok 5. Jeśli pozostała kwota BTC została wyzerowana, przestań sprawdzać kontroferty
                    if ($pozostalaKwotaBtc == 0) {
                        break;
                    }
                }
                //Krok 6. Zapisz nową ofertę

                $nowaOferta->pozostala_kwota_btc = $pozostalaKwotaBtc;

                if ($pozostalaKwotaBtc == 0) {
                    $nowaOferta->status = "Zakończona";
                } else {
                    $nowaOferta->status = "Aktywna";
                }

                $uzytkownik->saldo_pln = $saldoPln;
                $uzytkownik->saldo_btc = $saldoBtc;
                $zapisanoUzytkownika = $uzytkownik->save();

                if (!$zapisanoUzytkownika) {
                    DB::rollback();
                    Log::channel('bledy')->error('Błąd podczas zapisywania wystawiającego ofertę', ['wystawiajacyOferte' => $uzytkownik]);
                    return response()->json(['komunikat' => 'Wystąpił błąd podczas dodawania oferty'], 521);
                }

                $zapisanoOferte = $uzytkownik->ofertyJakoWystawiajacy()->save($nowaOferta);

                if (!$zapisanoOferte) {
                    DB::rollback();
                    Log::channel('bledy')->error('Błąd podczas zapisywania nowej oferty', ['nowaOferta' => $nowaOferta]);
                    return response()->json(['komunikat' => 'Wystąpił błąd podczas dodawania oferty'], 521);
                }

                $historiaSaldaUzytkownikaOferta = new HistoriaSaldaUzytkownikow();
                $historiaSaldaUzytkownikaOferta->uzytkownik()->associate($uzytkownik);
                $historiaSaldaUzytkownikaOferta->rodzaj_zmiany_salda = "Odjęcie";
                if ($nowaOferta->typ === "Zakup") {
                    $historiaSaldaUzytkownikaOferta->rodzaj_operacji = "Wystawienie oferty zakupu BTC";
                    $historiaSaldaUzytkownikaOferta->rodzaj_salda = "PLN";
                    $historiaSaldaUzytkownikaOferta->saldo_btc_po_zakonczeniu_operacji = $saldoBtcWystawiajacegoPrzedWystawieniemOferty;
                    $historiaSaldaUzytkownikaOferta->saldo_pln_po_zakonczeniu_operacji = $uzytkownik->saldo_pln;
                    $historiaSaldaUzytkownikaOferta->kwota_pln = $nowaOferta->kwota_pln;
                } elseif ($nowaOferta->typ === "Sprzedaż") {
                    $historiaSaldaUzytkownikaOferta->rodzaj_operacji = "Wystawienie oferty sprzedaży BTC";
                    $historiaSaldaUzytkownikaOferta->rodzaj_salda = "BTC";
                    $historiaSaldaUzytkownikaOferta->saldo_btc_po_zakonczeniu_operacji = $uzytkownik->saldo_btc;
                    $historiaSaldaUzytkownikaOferta->saldo_pln_po_zakonczeniu_operacji = $saldoPlnWystawiajacegoPrzedWystawieniemOferty;
                    $historiaSaldaUzytkownikaOferta->kwota_btc = $nowaOferta->kwota_btc;
                }
                $historiaSaldaUzytkownikaOferta->oferta_id = $nowaOferta->id;
                $historiaSaldaUzytkownikaOferta->saldo_pln_przed_rozpoczeciem_operacji = $saldoPlnWystawiajacegoPrzedWystawieniemOferty;
                $historiaSaldaUzytkownikaOferta->saldo_btc_przed_rozpoczeciem_operacji = $saldoBtcWystawiajacegoPrzedWystawieniemOferty;

                $zapisanoHistorieSaldaUzytkownika = $historiaSaldaUzytkownikaOferta->save();

                if (!$zapisanoHistorieSaldaUzytkownika) {
                    DB::rollback();
                    Log::channel('bledy')->error('Błąd podczas zapisywania historii użytkownika przy wystawianiu oferty', ['nowaHistoriaUzytkownika' => $historiaSaldaUzytkownikaOferta]);
                    return response()->json(['komunikat' => 'Wystąpił błąd podczas dodawania oferty'], 521);
                }

                if (!empty($transakcje)) {
                    $liczbaTransakcji = count($transakcje);
                    $zapisanoTransakcje = $nowaOferta->transakcjeJakoWystawiona()->saveMany($transakcje);

                    if (count($zapisanoTransakcje) !== $liczbaTransakcji) {
                        DB::rollback();
                        Log::channel('bledy')->error('Błąd podczas zapisywania transakcji do oferty', ['transakcje' => $transakcje]);
                        return response()->json(['komunikat' => 'Wystąpił błąd podczas dodawania oferty'], 521);
                    }

                    $licznikZapisanychHistoriiSaldaUzytkownikaTransakcji = 0;
                    foreach ($historieSaldaUzytkownikaTransakcje as $historiaSaldaUzytkownikaTransakcja) {
                        $transakcja = $historiaSaldaUzytkownikaTransakcja->transakcja;
                        $historiaSaldaUzytkownikaTransakcja->transakcja()->associate($transakcja);
                        if ($historiaSaldaUzytkownikaTransakcja->transakcja->id === null) {
                            break;
                        }
                        $zapisanoHistorieUzytkownikaTransakcji = $historiaSaldaUzytkownikaTransakcja->save();
                        if ($zapisanoHistorieUzytkownikaTransakcji) {
                            $licznikZapisanychHistoriiSaldaUzytkownikaTransakcji++;
                        }
                    }
                    if ($licznikZapisanychHistoriiSaldaUzytkownikaTransakcji !== (2 * $liczbaTransakcji)) {
                        DB::rollback();
                        Log::channel('bledy')->error('Błąd podczas zapisywania historii salda użytkownika przy dokonywaniu transakcji', ['historieSaldaUzytkownikaDlaTransakcji' => $historieSaldaUzytkownikaTransakcje]);
                        return response()->json(['komunikat' => 'Wystąpił błąd podczas dodawania oferty'], 521);
                    }

                    $saldoBitkantor = SaldoBitkantor::lockForUpdate()->firstOrFail();
                    $saldoBitkantorPln = $saldoBitkantor->saldo_pln;
                    $saldoBitkantorBtc = $saldoBitkantor->saldo_btc;

                    foreach ($transakcje as $transakcja) {
                        $transakcja->refresh();

                        if ($transakcja->prowizja_bitkantor_pln) {
                            $nowaHistoriaSaldaBitkantor = new HistoriaSaldaBitkantor();
                            $nowaHistoriaSaldaBitkantor->rodzaj_salda = "PLN";
                            $nowaHistoriaSaldaBitkantor->rodzaj_zmiany_salda = "Dodanie";
                            $nowaHistoriaSaldaBitkantor->saldo_pln_przed_rozpoczeciem_operacji = $saldoBitkantorPln;
                            $nowaHistoriaSaldaBitkantor->saldo_btc_przed_rozpoczeciem_operacji = $saldoBitkantorBtc;
                            $nowaHistoriaSaldaBitkantor->rodzaj_operacji = "Prowizja transakcyjna";
                            $nowaHistoriaSaldaBitkantor->kwota_pln = $transakcja->prowizja_bitkantor_pln;
                            $saldoBitkantorPln = $this->pomocnikLiczbowy->dodajXdoY($saldoBitkantorPln, $transakcja->prowizja_bitkantor_pln, 2);
                            $nowaHistoriaSaldaBitkantor->saldo_pln_po_zakonczeniu_operacji = $saldoBitkantorPln;
                            $nowaHistoriaSaldaBitkantor->saldo_btc_po_zakonczeniu_operacji = $saldoBitkantorBtc;

                            $zapisanoHistorieSaldaBitkantor = $transakcja->historiaSaldaBitkantor()->save($nowaHistoriaSaldaBitkantor);

                            if (!$zapisanoHistorieSaldaBitkantor) {
                                DB::rollback();
                                Log::channel('bledy')->error('Błąd podczas zapisywania historii salda Bitkantor przy wystawianiu oferty', ['historiaSaldaBitkantor' => $nowaHistoriaSaldaBitkantor]);
                                return response()->json(['komunikat' => 'Wystąpił błąd podczas dodawania oferty'], 521);
                            }

                        }
                        if ($transakcja->prowizja_bitkantor_btc) {
                            $nowaHistoriaSaldaBitkantor = new HistoriaSaldaBitkantor();
                            $nowaHistoriaSaldaBitkantor->rodzaj_salda = "BTC";
                            $nowaHistoriaSaldaBitkantor->rodzaj_zmiany_salda = "Dodanie";
                            $nowaHistoriaSaldaBitkantor->saldo_pln_przed_rozpoczeciem_operacji = $saldoBitkantorPln;
                            $nowaHistoriaSaldaBitkantor->saldo_btc_przed_rozpoczeciem_operacji = $saldoBitkantorBtc;
                            $nowaHistoriaSaldaBitkantor->rodzaj_operacji = "Prowizja transakcyjna";
                            $nowaHistoriaSaldaBitkantor->kwota_btc = $transakcja->prowizja_bitkantor_btc;
                            $saldoBitkantorBtc = $this->pomocnikLiczbowy->dodajXdoY($saldoBitkantorBtc, $transakcja->prowizja_bitkantor_btc, 8);
                            $nowaHistoriaSaldaBitkantor->saldo_pln_po_zakonczeniu_operacji = $saldoBitkantorPln;
                            $nowaHistoriaSaldaBitkantor->saldo_btc_po_zakonczeniu_operacji = $saldoBitkantorBtc;

                            $zapisanoHistorieSaldaBitkantor = $transakcja->historiaSaldaBitkantor()->save($nowaHistoriaSaldaBitkantor);

                            if (!$zapisanoHistorieSaldaBitkantor) {
                                DB::rollback();
                                Log::channel('bledy')->error('Błąd podczas zapisywania historii salda Bitkantor przy wystawianiu oferty', ['historiaSaldaBitkantor' => $nowaHistoriaSaldaBitkantor]);
                                return response()->json(['komunikat' => 'Wystąpił błąd podczas dodawania oferty'], 521);
                            }
                        }

                    }

                    $saldoBitkantor->saldo_pln = $saldoBitkantorPln;
                    $saldoBitkantor->saldo_btc = $saldoBitkantorBtc;

                    $zapisanoSaldoBitkantor = $saldoBitkantor->save();

                    if (!$zapisanoSaldoBitkantor) {
                        DB::rollback();
                        Log::channel('bledy')->error('Błąd podczas zapisywania salda Bitkantor przy wystawianiu oferty', ['saldoBitkantor' => $saldoBitkantor]);
                        return response()->json(['komunikat' => 'Wystąpił błąd podczas dodawania oferty'], 521);
                    }
                }
                DB::commit();
                Log::channel('informacje')->info('Zakończono dodawanie oferty', ['saldoKoncowePLN' => $saldoPln, 'saldoKoncoweBTC' => $saldoBtc, 'idOferty' => $nowaOferta->id]);

                return response()->json(['wystawionaOferta' => $nowaOferta, 'aktualneSaldoPlnWystawiajacego' => $saldoPln, 'aktualneSaldoBtcWystawiajacego' => $saldoBtc], 200);
            } catch (Exception $wyjatek) {
                DB::rollback();
                Log::channel('wyjatki')->critical('Wyjątek przy dodawaniu nowej oferty', [
                    'wyjatek' => $wyjatek,
                ]);
                return response()->json(['komunikat' => 'Wystąpił błąd podczas dodawania oferty'], 521);
            }
            // Koniec transakcji

        } else {
            // Problem z zaokrąglonymi wartościami kwot
            Log::channel('bledy')->error('Błąd podczas walidacji wyliczonych kwot oferty');
            return response()->json(['komunikat' => 'Wprowadzono nieprawidłową kwotę w ofercie'], 521);
        }
    }

    /**
     * Zmienia status oferty na "Anulowana" i przywraca środki na saldo
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function anulujOferte(Request $request, Oferta $oferta)
    {

        $this->authorize('anulujOferte', $oferta);

        DB::beginTransaction();

        try {

            $oferta = Oferta::where('id', $oferta->id)->lockForUpdate()->firstOrFail();
            $wystawiajacyOferte = $oferta->wystawiajacy()->lockForUpdate()->firstOrFail();

            if ($oferta->status === "Aktywna") {

                $domyslneParametryLogow = [
                    'idUzytkownika' => $wystawiajacyOferte->id,
                    'idOferty' => $oferta->id,
                ];

                Log::channel('informacje')->withContext($domyslneParametryLogow);
                Log::channel('bledy')->withContext($domyslneParametryLogow);
                Log::channel('wyjatki')->withContext($domyslneParametryLogow);

                $pozostalaKwotaBtc = $oferta->pozostala_kwota_btc;
                $saldoPlnWystawiajacegoOferte = $wystawiajacyOferte->saldo_pln;
                $saldoBtcWystawiajacegoOferte = $wystawiajacyOferte->saldo_btc;
                // $oferta->saldo_pln_wystawiajacego_przed_zakonczeniem_lub_anulowaniem = $saldoPlnWystawiajacegoOferte;
                // $oferta->saldo_btc_wystawiajacego_przed_zakonczeniem_lub_anulowaniem = $saldoBtcWystawiajacegoOferte;

                Log::channel('informacje')->info('Rozpoczęto anulowanie oferty', ['pozostalaKwotaBtc' => $pozostalaKwotaBtc, 'saldoPoczatkowePlnWystawiajacegoOferte' => $saldoPlnWystawiajacegoOferte, 'saldoPoczatkoweBtcWystawiajacegoOferte' => $saldoBtcWystawiajacegoOferte]);

                $historiaSaldaUzytkownikaAnulowanieOferty = new HistoriaSaldaUzytkownikow();
                $historiaSaldaUzytkownikaAnulowanieOferty->uzytkownik()->associate($wystawiajacyOferte);
                $historiaSaldaUzytkownikaAnulowanieOferty->oferta()->associate($oferta);
                $historiaSaldaUzytkownikaAnulowanieOferty->rodzaj_zmiany_salda = "Dodanie";
                $historiaSaldaUzytkownikaAnulowanieOferty->saldo_pln_przed_rozpoczeciem_operacji = $saldoPlnWystawiajacegoOferte;
                $historiaSaldaUzytkownikaAnulowanieOferty->saldo_btc_przed_rozpoczeciem_operacji = $saldoBtcWystawiajacegoOferte;

                if ($oferta->typ === "Sprzedaż") {

                    $saldoBtcWystawiajacegoOferte = $this->pomocnikLiczbowy->dodajXdoY($saldoBtcWystawiajacegoOferte, $pozostalaKwotaBtc, 8);
                    $wystawiajacyOferte->saldo_btc = $saldoBtcWystawiajacegoOferte;
                    $historiaSaldaUzytkownikaAnulowanieOferty->rodzaj_operacji = "Anulowanie oferty sprzedaży BTC";
                    $historiaSaldaUzytkownikaAnulowanieOferty->rodzaj_salda = "BTC";
                    $historiaSaldaUzytkownikaAnulowanieOferty->kwota_btc = $pozostalaKwotaBtc;
                } elseif ($oferta->typ === "Zakup") {

                    $pozostalaKwotaPln = $this->pomocnikLiczbowy->zaokraglWDolPoPrzecinku($this->pomocnikLiczbowy->pomnozXprzezY($oferta->kurs, $pozostalaKwotaBtc, 10), 2);

                    $saldoPlnWystawiajacegoOferte = $this->pomocnikLiczbowy->dodajXdoY($saldoPlnWystawiajacegoOferte, $pozostalaKwotaPln, 2);
                    $wystawiajacyOferte->saldo_pln = $saldoPlnWystawiajacegoOferte;
                    $historiaSaldaUzytkownikaAnulowanieOferty->rodzaj_operacji = "Anulowanie oferty zakupu BTC";
                    $historiaSaldaUzytkownikaAnulowanieOferty->rodzaj_salda = "PLN";
                    $historiaSaldaUzytkownikaAnulowanieOferty->kwota_pln = $pozostalaKwotaPln;
                }

                $historiaSaldaUzytkownikaAnulowanieOferty->saldo_btc_po_zakonczeniu_operacji = $saldoBtcWystawiajacegoOferte;
                $historiaSaldaUzytkownikaAnulowanieOferty->saldo_pln_po_zakonczeniu_operacji = $saldoPlnWystawiajacegoOferte;

                $oferta->status = "Anulowana";
                $zapisanoWystawiajacegoOferte = $wystawiajacyOferte->save();

                if (!$zapisanoWystawiajacegoOferte) {
                    // Błąd podczas zapisywania użytkownika
                    DB::rollback();
                    Log::channel('bledy')->error('Błąd podczas zapisywania anulującego ofertę', ['wystawiajacyKontroferte' => $wystawiajacyOferte]);
                    return response()->json(['komunikat' => 'Wystąpił błąd podczas anulowania oferty'], 520);
                }

                $zapisanoOferte = $oferta->save();

                if (!$zapisanoOferte) {
                    // Błąd podczas zapisywania anulowanej oferty
                    DB::rollback();
                    Log::channel('bledy')->error('Błąd podczas zapisywania anulowanej oferty', ['anulowanaOferta' => $oferta]);

                    return response()->json(['komunikat' => 'Wystąpił błąd podczas anulowania oferty'], 520);
                }

                $zapisanoHistorieSaldaUzytkownika = $historiaSaldaUzytkownikaAnulowanieOferty->save();

                if (!$zapisanoHistorieSaldaUzytkownika) {
                    DB::rollback();
                    Log::channel('bledy')->error('Błąd podczas zapisywania historii użytkownika przy anulowaniu oferty', ['nowaHistoriaUzytkownika' => $historiaSaldaUzytkownikaAnulowanieOferty]);
                    return response()->json(['komunikat' => 'Wystąpił błąd podczas anulowania oferty'], 520);
                }
                DB::commit();
                Log::channel('informacje')->info('Zakończono anulowanie oferty', ['saldoKoncowePlnWystawiajacegoOferte' => $saldoPlnWystawiajacegoOferte, 'saldoKoncoweBtcWystawiajacegoOferte' => $saldoBtcWystawiajacegoOferte]);

                return response()->json(['aktualneSaldoPlnWystawiajacego' => $saldoPlnWystawiajacegoOferte, 'aktualneSaldoBtcWystawiajacego' => $saldoBtcWystawiajacegoOferte], 200);
            } else {
                return response()->json(['komunikat' => 'Oferta nie jest już aktywna'], 520);
            }
        } catch (Exception $wyjatek) {
            DB::rollback();
            Log::channel('wyjatki')->critical('Wyjątek przy anulowaniu oferty', [
                'wyjatek' => $wyjatek,
            ]);
            return response()->json(['komunikat' => 'Wystąpił błąd podczas anulowania oferty'], 520);
        }
    }
}
