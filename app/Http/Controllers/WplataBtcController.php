<?php

namespace App\Http\Controllers;

use App\Libraries\BitcoinApi;
use App\Libraries\PomocnikLiczbowy;
use App\Models\HistoriaSaldaUzytkownikow;
use App\Models\TransakcjaBlockchain;
use App\Models\User;
use App\Models\WplataBtc;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WplataBtcController extends Controller
{

    public function __construct()
    {
        $this->pomocnikLiczbowy = new PomocnikLiczbowy();
    }

    // Cronjob
    public function wykryjWplatyBtc()
    {
        // TODO: ulepszyć logi

        $bitcoinApi = new BitcoinApi();

        $transakcjePrzychodzace = $bitcoinApi->listaOstatnichTransakcjiPrzychodzacych();

        if ($transakcjePrzychodzace) {

            try {

                // $domyslneParametryLogow = [
                //     'idUzytkownika' => $uzytkownik->id,
                //     'kwotaWyplatyBtc' => $kwotaWyplatyBtc,
                //     'prowizjaBtc' => $prowizjaZaWyplateBtc,
                //     'adresPortfelaDoWyplaty' => $adresPortfelaDoWyplaty];

                // Log::channel('informacje')->withContext($domyslneParametryLogow);
                // Log::channel('bledy')->withContext($domyslneParametryLogow);
                // Log::channel('wyjatki')->withContext($domyslneParametryLogow);

                $ostatnioWykryteWplatyBtc = WplataBtc::with("transakcjaBlockchain")->latest()->limit(1000)->get();

                foreach ($transakcjePrzychodzace as $transakcjaPrzychodzaca) {
                    $kategoriaTransakcji = $transakcjaPrzychodzaca["category"];
                    $liczbaPotwierdzen = $transakcjaPrzychodzaca["confirmations"];
                    $txid = $transakcjaPrzychodzaca["txid"];
                    $adresPortfela = $transakcjaPrzychodzaca["address"];
                    $kwotaWplatyBtc = $transakcjaPrzychodzaca["amount"];
                    if ($kategoriaTransakcji === "receive") {

                        $czyWplataJestJuzWOstatnioWykrytych = $ostatnioWykryteWplatyBtc->contains(function ($ostatnioWykrytaWplataBtc, $klucz) use ($adresPortfela, $txid) {
                            return $ostatnioWykrytaWplataBtc->adres_portfela_odbiorcy === $adresPortfela && $ostatnioWykrytaWplataBtc->transakcjaBlockchain->txid == $txid;
                        });

                        // Sprawdzam, czy wpłata została już wcześniej wykryta
                        if ($czyWplataJestJuzWOstatnioWykrytych || WplataBtc::where('adres_portfela_odbiorcy', $adresPortfela)->whereRelation('transakcjaBlockchain', 'txid', $txid)->exists()) {
                            continue;
                        } else {
                            // Sprawdzam, czy istnieje użytkownik o takim adresie portfela
                            $uzytkownik = User::where("adres_portfela", $adresPortfela)->first();
                            if (!$uzytkownik) {
                                continue;
                            }

                            DB::beginTransaction();

                            try {

                                $transakcjaBlockchainWplaty = TransakcjaBlockchain::where("txid", $txid)->first();

                                if (!$transakcjaBlockchainWplaty) {
                                    $transakcjaBlockchainWplaty = new TransakcjaBlockchain();
                                    $transakcjaBlockchainWplaty->txid = $txid;
                                    $transakcjaBlockchainWplaty->liczba_potwierdzen = $liczbaPotwierdzen;
                                    $transakcjaBlockchainWplaty->save();
                                    if (!$transakcjaBlockchainWplaty) {
                                        DB::rollback();
                                        Log::channel('admin')->error('Błąd podczas zapisywania transakcji blockchain dla wpłaty BTC', ["transakcjaBlockchain" => $transakcjaBlockchainWplaty]);
                                        continue;
                                    }
                                }

                                $nowaWplataBtc = new WplataBtc();
                                $nowaWplataBtc->transakcjaBlockchain()->associate($transakcjaBlockchainWplaty);
                                $nowaWplataBtc->uzytkownik()->associate($uzytkownik);
                                $nowaWplataBtc->kwota_btc = $kwotaWplatyBtc;
                                $nowaWplataBtc->adres_portfela_odbiorcy = $adresPortfela;
                                $nowaWplataBtc->status = "Wykryta";
                                $zapisanoWplateBtc = $nowaWplataBtc->save();

                                if ($zapisanoWplateBtc) {
                                    DB::commit();
                                } else {
                                    DB::rollback();
                                    Log::channel('bledy')->error('Błąd podczas zapisywania nowo wykrytej wpłaty BTC');
                                }

                            } catch (Exception $wyjatek) {
                                DB::rollback();
                                Log::channel('wyjatki')->critical('Wyjątek przy wykrywaniu wypłaty BTC', [
                                    'wyjatek' => $wyjatek]);
                            }

                        }

                    }
                }
                Log::channel('informacje')->info('Wykrywanie wpłat BTC zostało zakończone pomyślnie');
                return true;
            } catch (Exception $wyjatek) {
                Log::channel('wyjatki')->critical('Wyjątek przy wykrywaniu wpłat BTC', [
                    'wyjatek' => $wyjatek]);
                return false;
            }

        } else {
            Log::channel('wyjatki')->critical('Nie wykryto żadnych transakcji przez Bitcoin API');
            return false;
        }

    }

    // Cronjob
    public function zaksiegujWplatyBtc()
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

            $wykryteWplatyBtc = WplataBtc::where('status', 'Wykryta')->lockForUpdate()->get();

            foreach ($wykryteWplatyBtc as $wplataBtc) {

                $transakcjaBlockchainWplaty = $wplataBtc->transakcjaBlockchain()->lockForUpdate()->firstOrFail();
                $txidWplaty = $wplataBtc->transakcjaBlockchain->txid;
                $liczbaPotwierdzenTransakcji = $transakcjaBlockchainWplaty->liczba_potwierdzen;

                if ($liczbaPotwierdzenTransakcji < 6) {

                    $informacjeOTransakcji = $bitcoinApi->informacjeOTransakcji($txidWplaty);
                    if ($informacjeOTransakcji) {
                        $liczbaPotwierdzenTransakcji = $informacjeOTransakcji["confirmations"];

                        if ($liczbaPotwierdzenTransakcji !== $transakcjaBlockchainWplaty->liczba_potwierdzen) {

                            $transakcjaBlockchainWplaty->liczba_potwierdzen = $liczbaPotwierdzenTransakcji;

                            $zapisanoTransakcjeBlockchainWplaty = $transakcjaBlockchainWplaty->save();

                            if (!$zapisanoTransakcjeBlockchainWplaty) {
                                DB::rollback();
                                Log::channel('bledy')->error('Błąd podczas zapisywania transakcji blockchain przy księgowaniu wpłaty BTC', ['transakcjaBlockchain' => $transakcjaBlockchainWplaty]);
                                return false;
                            }

                        }

                        if ($liczbaPotwierdzenTransakcji < 6) {
                            continue;
                        }

                    } else {

                        Log::channel('wyjatki')->critical('Nie uzyskano informacji o transakcji przez Bitcoin API', ["txid" => $txidWplaty]);
                        continue;

                    }
                }

                $adresPortfela = $wplataBtc->adres_portfela_odbiorcy;

                $uzytkownik = $wplataBtc->uzytkownik()->lockForUpdate()->firstOrFail();

                $saldoBtcUzytkownikaPrzedWplata = $uzytkownik->saldo_btc;
                $kwotaBtc = $wplataBtc->kwota_btc;
                $saldoBtcUzytkownikaPoWplacie = $this->pomocnikLiczbowy->dodajXdoY($saldoBtcUzytkownikaPrzedWplata,$kwotaBtc,8);
                $wplataBtc->status = "Zakończona";
                $uzytkownik->saldo_btc = $saldoBtcUzytkownikaPoWplacie;

                $historiaSaldaUzytkownikaWplataBtc = new HistoriaSaldaUzytkownikow();
                $historiaSaldaUzytkownikaWplataBtc->uzytkownik()->associate($uzytkownik);
                $historiaSaldaUzytkownikaWplataBtc->wplataBtc()->associate($wplataBtc);
                $historiaSaldaUzytkownikaWplataBtc->rodzaj_salda = "BTC";
                $historiaSaldaUzytkownikaWplataBtc->rodzaj_zmiany_salda = "Dodanie";
                $historiaSaldaUzytkownikaWplataBtc->rodzaj_operacji = "Wpłata BTC";
                $historiaSaldaUzytkownikaWplataBtc->kwota_btc = $kwotaBtc;
                $historiaSaldaUzytkownikaWplataBtc->saldo_btc_przed_rozpoczeciem_operacji = $saldoBtcUzytkownikaPrzedWplata;
                $historiaSaldaUzytkownikaWplataBtc->saldo_pln_przed_rozpoczeciem_operacji = $uzytkownik->saldo_pln;

                $historiaSaldaUzytkownikaWplataBtc->saldo_btc_po_zakonczeniu_operacji = $saldoBtcUzytkownikaPoWplacie;
                $historiaSaldaUzytkownikaWplataBtc->saldo_pln_po_zakonczeniu_operacji = $uzytkownik->saldo_pln;

                $zapisanoHistorieSaldaUzytkownika = $historiaSaldaUzytkownikaWplataBtc->save();

                if (!$zapisanoHistorieSaldaUzytkownika) {
                    DB::rollback();
                    Log::channel('bledy')->error('Błąd podczas zapisywania historii użytkownika przy księgowaniu wpłaty BTC', ['nowaHistoriaUzytkownika' => $historiaSaldaUzytkownikaWplataBtc]);
                    return false;
                }

                $zapisanoUzytkownika = $uzytkownik->save();

                if (!$zapisanoUzytkownika) {
                    DB::rollback();
                    Log::channel('bledy')->error('Błąd podczas zapisywania użytkownika przy księgowaniu wpłaty BTC', ['uzytkownik' => $uzytkownik]);
                    return false;
                }

                $zapisanoWplateBtc = $wplataBtc->save();

                if (!$zapisanoWplateBtc) {
                    DB::rollback('ROLLBACK');
                    Log::channel('bledy')->error('Błąd podczas księgowania wpłaty BTC');
                    return false;
                }

            }
            DB::commit();
            Log::channel('informacje')->info('Księgowanie wpłat BTC zostało zakończone pomyślnie');
            return true;
        } catch (Exception $wyjatek) {
            DB::rollback();
            Log::channel('wyjatki')->critical('Wyjątek przy księgowaniu wpłat BTC', [
                'wyjatek' => $wyjatek]);
            return false;
        }

    }

    // // Cronjob
    // // Poprzednia wersja funkcji wykrywającej wpłaty
    // public function wykryjWplatyBtc()
    // {
    //     // TODO: ulepszyć logi

    //     $bitcoinApi = new BitcoinApi();

    //     $transakcjeDlaPoszczegolnychAdresow = $bitcoinApi->transakcjeDlaAdresow();

    //     if ($transakcjeDlaPoszczegolnychAdresow) {

    //         try {

    //             // $domyslneParametryLogow = [
    //             //     'idUzytkownika' => $uzytkownik->id,
    //             //     'kwotaWyplatyBtc' => $kwotaWyplatyBtc,
    //             //     'prowizjaBtc' => $prowizjaZaWyplateBtc,
    //             //     'adresPortfelaDoWyplaty' => $adresPortfelaDoWyplaty];

    //             // Log::channel('informacje')->withContext($domyslneParametryLogow);
    //             // Log::channel('bledy')->withContext($domyslneParametryLogow);
    //             // Log::channel('wyjatki')->withContext($domyslneParametryLogow);

    //             foreach ($transakcjeDlaPoszczegolnychAdresow as $transakcjeDlaKonkretnegoAdresu) {
    //                 $etykieta = $transakcjeDlaKonkretnegoAdresu["label"];
    //                 if ($etykieta === "adresy_uzytkownikow") {
    //                     $txids = $transakcjeDlaKonkretnegoAdresu["txids"];
    //                     $adresPortfela = $transakcjeDlaKonkretnegoAdresu["address"];
    //                     foreach ($txids as $txid) {

    //                         if (WplataBtc::where('adres_portfela_odbiorcy', $adresPortfela)->whereRelation('transakcjaBlockchain', 'txid', $txid)->exists()) {
    //                             continue;
    //                         } else {
    //                             $otrzymanaKwotaBtcDlaTransakcji = 0.00000000;
    //                             $informacjeOTransakcji = $bitcoinApi->informacjeOTransakcji($txid);
    //                             if ($informacjeOTransakcji) {
    //                                 $liczbaPotwierdzen = $informacjeOTransakcji["confirmations"];
    //                                 if ($liczbaPotwierdzen >= 6) {

    //                                     $wszystkiePrzelewy = $informacjeOTransakcji["details"];

    //                                     foreach ($wszystkiePrzelewy as $przelew) {

    //                                         if ($przelew["category"] === "receive" && $przelew["address"] === $adresPortfela) {
    //                                             $otrzymanaKwotaBtcDlaTransakcji += $przelew["amount"];
    //                                         }
    //                                     }

    //                                     DB::beginTransaction();

    //                                     try {

    //                                         $transakcjaBlockchainWplaty = TransakcjaBlockchain::where("txid", $txid)->where("typ","Wpłata")->first();

    //                                         if (!$transakcjaBlockchainWplaty) {
    //                                             $transakcjaBlockchainWplaty = new TransakcjaBlockchain();
    //                                             $transakcjaBlockchainWplaty->txid = $txid;
    //                                             $transakcjaBlockchainWplaty->typ = "Wpłata";
    //                                             $transakcjaBlockchainWplaty->save();
    //                                             if (!$transakcjaBlockchainWplaty) {
    //                                                 DB::rollback();
    //                                                 Log::channel('admin')->error('Błąd podczas zapisywania transakcji blockchain dla wpłaty BTC', ["transakcjaBlockchain" => $transakcjaBlockchainWplaty]);
    //                                                 continue;
    //                                             }
    //                                         }

    //                                         $nowaWplataBtc = new WplataBtc();
    //                                         $nowaWplataBtc->transakcjaBlockchain()->associate($transakcjaBlockchainWplaty);
    //                                         $nowaWplataBtc->kwota_btc = $otrzymanaKwotaBtcDlaTransakcji;
    //                                         $nowaWplataBtc->adres_portfela_odbiorcy = $adresPortfela;
    //                                         $nowaWplataBtc->status = "Wykryta";
    //                                         $zapisanoWplateBtc = $nowaWplataBtc->save();

    //                                         if ($zapisanoWplateBtc) {
    //                                             DB::commit();
    //                                         } else {
    //                                             DB::rollback();
    //                                             Log::channel('bledy')->error('Błąd podczas zapisywania nowo wykrytej wpłaty BTC');
    //                                         }

    //                                     } catch (Exception $wyjatek) {
    //                                         DB::rollback();
    //                                         Log::channel('wyjatki')->critical('Wyjątek przy wykrywaniu wypłaty BTC', [
    //                                             'wyjatek' => $wyjatek]);
    //                                     }

    //                                 }

    //                             }

    //                         }

    //                     }

    //                 }
    //             }
    //             Log::channel('informacje')->info('Wykrywanie wpłat BTC zostało zakończone pomyślnie');
    //             return true;
    //         } catch (Exception $wyjatek) {
    //             Log::channel('wyjatki')->critical('Wyjątek przy wykrywaniu wpłat BTC', [
    //                 'wyjatek' => $wyjatek]);
    //             return false;
    //         }

    //     } else {
    //         Log::channel('wyjatki')->critical('Nie wykryto żadnych transakcji przez Bitcoin API');
    //         return false;
    //     }

    // }

}
