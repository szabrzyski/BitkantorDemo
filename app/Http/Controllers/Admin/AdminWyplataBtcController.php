<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Libraries\BitcoinApi;
use App\Libraries\PomocnikLiczbowy;
use App\Models\HistoriaSaldaUzytkownikow;
use App\Models\TransakcjaBlockchain;
use App\Models\WyplataBtc;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminWyplataBtcController extends Controller
{

    public function __construct()
    {
        $this->pomocnikLiczbowy = new PomocnikLiczbowy();
    }

    public function wyplatyBtc(Request $request)
    {

        $wyplatyBtc = WyplataBtc::with('uzytkownik', 'transakcjaBlockchain')->orderByRaw("FIELD(status, 'Zlecona', 'Realizowana', 'Wysłana', 'Zakończona', 'Anulowana')")->orderBy("updated_at", "DESC")->paginate(15);

        return view('admin.wyplatyBtc')->with('wyplatyBtc', $wyplatyBtc)->with('wyniki', $wyplatyBtc);

    }

    /**
     * Zmienia status wypłaty na "Anulowana" i przywraca środki na saldo
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Models\WyplataBtc $wyplataBtc
     * @return \Illuminate\Http\Response
     */
    public function anulujWyplateBtc(Request $request, WyplataBtc $wyplataBtc)
    {

        $this->authorize('uprawnieniaAdmina', $request->user());

        DB::beginTransaction();

        try {

            $wyplataBtc = WyplataBtc::where('id', $wyplataBtc->id)->lockForUpdate()->firstOrFail();

            if (in_array($wyplataBtc->status, ["Zlecona", "Realizowana"])) {

                $wyplacajacyBtc = $wyplataBtc->uzytkownik()->lockForUpdate()->firstOrFail();
                $kwotaWyplatyBtc = $wyplataBtc->kwota_btc;
                $saldoBtcWyplacajacego = $wyplacajacyBtc->saldo_btc;

                $historiaSaldaUzytkownikaAnulowanieWyplatyBtc = new HistoriaSaldaUzytkownikow();
                $historiaSaldaUzytkownikaAnulowanieWyplatyBtc->uzytkownik()->associate($wyplacajacyBtc);
                $historiaSaldaUzytkownikaAnulowanieWyplatyBtc->wyplataBtc()->associate($wyplataBtc);
                $historiaSaldaUzytkownikaAnulowanieWyplatyBtc->rodzaj_salda = "BTC";
                $historiaSaldaUzytkownikaAnulowanieWyplatyBtc->rodzaj_zmiany_salda = "Dodanie";
                $historiaSaldaUzytkownikaAnulowanieWyplatyBtc->rodzaj_operacji = "Anulowanie wypłaty BTC";
                $historiaSaldaUzytkownikaAnulowanieWyplatyBtc->kwota_btc = $kwotaWyplatyBtc;
                $historiaSaldaUzytkownikaAnulowanieWyplatyBtc->saldo_btc_przed_rozpoczeciem_operacji = $saldoBtcWyplacajacego;
                $historiaSaldaUzytkownikaAnulowanieWyplatyBtc->saldo_pln_przed_rozpoczeciem_operacji = $wyplacajacyBtc->saldo_pln;

                $domyslneParametryLogow = [
                    'idUzytkownika' => $wyplacajacyBtc->id,
                    'idWyplatyBtc' => $wyplataBtc->id,
                    'kwotaWyplatyBtc' => $kwotaWyplatyBtc,
                    'saldoBtcWyplacajacegoPrzedAnulowaniemWyplaty' => $saldoBtcWyplacajacego];

                Log::channel('admin')->withContext($domyslneParametryLogow);

                Log::channel('admin')->info('Rozpoczęto anulowanie wypłaty BTC');

                $saldoBtcWyplacajacego = $this->pomocnikLiczbowy->dodajXdoY($saldoBtcWyplacajacego, $kwotaWyplatyBtc, 8);
                $wyplacajacyBtc->saldo_btc = $saldoBtcWyplacajacego;

                $wyplataBtc->status = "Anulowana";
                $zapisanoWyplacajacegoBtc = $wyplacajacyBtc->save();

                if (!$zapisanoWyplacajacegoBtc) {

                    // Błąd podczas zapisywania użytkownika
                    DB::rollback();
                    Log::channel('admin')->error('Błąd podczas zapisywania anulującego wypłatę BTC');
                    return back()->with('blad', 'Wystąpił błąd podczas anulowania wypłaty BTC');
                }

                $zapisanoWyplateBtc = $wyplataBtc->save();

                if (!$zapisanoWyplateBtc) {

                    // Błąd podczas zapisywania anulowanej wypłaty
                    DB::rollback();
                    Log::channel('admin')->error('Błąd podczas zapisywania anulowanej wypłaty BTC', ['anulowanaWyplataBtc' => $wyplataBtc]);

                    return back()->with('blad', 'Wystąpił błąd podczas anulowania wypłaty BTC');

                }

                $historiaSaldaUzytkownikaAnulowanieWyplatyBtc->saldo_btc_po_zakonczeniu_operacji = $saldoBtcWyplacajacego;
                $historiaSaldaUzytkownikaAnulowanieWyplatyBtc->saldo_pln_po_zakonczeniu_operacji = $wyplacajacyBtc->saldo_pln;

                $zapisanoHistorieSaldaUzytkownika = $historiaSaldaUzytkownikaAnulowanieWyplatyBtc->save();

                if (!$zapisanoHistorieSaldaUzytkownika) {
                    DB::rollback();
                    Log::channel('admin')->error('Błąd podczas zapisywania historii użytkownika przy anulowaniu wypłaty BTC', ['nowaHistoriaUzytkownika' => $historiaSaldaUzytkownikaAnulowanieWyplatyBtc]);
                    return back()->with('blad', 'Wystąpił błąd podczas anulowania wypłaty BTC');
                }

                DB::commit();
                Log::channel('admin')->info('Zakończono anulowanie wypłaty BTC', ['saldoKoncoweBtcPoAnulowaniuWyplaty' => $saldoBtcWyplacajacego]);
                return back()->with('sukces', 'Anulowano wypłatę BTC');

            } else {
                DB::rollback();
                Log::channel('admin')->error('Próbowano anulować wysłaną wypłatę');
                return back()->with('blad', 'Wystąpił błąd podczas anulowania wypłaty BTC');
            }

        } catch (Exception $wyjatek) {
            DB::rollback();
            Log::channel('admin')->critical('Wyjątek przy anulowaniu wypłaty BTC', [
                'wyjatek' => $wyjatek]);
            return back()->with('blad', 'Wystąpił błąd podczas anulowania wypłaty BTC');
        }

    }

    /**
     * Zmienia status wypłaty na "Realizowana", aby wypłacający nie mógł jej anulować
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Models\WyplataBtc $wyplataBtc
     * @return \Illuminate\Http\Response
     */
    public function zablokujWyplateBtc(Request $request, WyplataBtc $wyplataBtc)
    {

        $this->authorize('uprawnieniaAdmina', $request->user());

        DB::beginTransaction();

        try {

            $wyplataBtc = WyplataBtc::where('id', $wyplataBtc->id)->lockForUpdate()->firstOrFail();

            if ($wyplataBtc->status === "Zlecona") {

                $domyslneParametryLogow = [
                    'idWyplatyBtc' => $wyplataBtc->id];

                Log::channel('admin')->withContext($domyslneParametryLogow);

                Log::channel('admin')->info('Rozpoczęto blokowanie wypłaty BTC');

                $wyplataBtc->status = "Realizowana";
                $zapisanoWyplateBtc = $wyplataBtc->save();

                if (!$zapisanoWyplateBtc) {

                    // Błąd podczas zapisywania anulowanej wypłaty
                    DB::rollback();
                    Log::channel('admin')->error('Błąd podczas zapisywania blokowanej wypłaty BTC', ['blokowanaWyplataBtc' => $wyplataBtc]);

                    return back()->with('blad', 'Wystąpił błąd podczas blokowania wypłaty BTC');

                }

                DB::commit();
                Log::channel('admin')->info('Zakończono blokowanie wypłaty BTC');
                return back()->with('sukces', 'Zablokowano wypłatę BTC');

            } else {
                DB::rollback();
                Log::channel('admin')->error('Próbowano zablokować wypłatę ze statusem innym niż "Zlecona"');
                return back()->with('blad', 'Wystąpił błąd podczas blokowania wypłaty BTC');
            }

        } catch (Exception $wyjatek) {
            DB::rollback();
            Log::channel('admin')->critical('Wyjątek przy blokowaniu wypłaty BTC', [
                'wyjatek' => $wyjatek]);
            return back()->with('blad', 'Wystąpił błąd podczas blokowania wypłaty BTC');
        }

    }

    /**
     * Zmienia status wypłaty na "Wysłana", która zostanie następnie obsłużona przez crona
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Models\WyplataBtc $wyplataBtc
     * @return \Illuminate\Http\Response
     */
    public function zrealizujWyplateBtc(Request $request, WyplataBtc $wyplataBtc)
    {

        $this->authorize('uprawnieniaAdmina', $request->user());

        $walidator = Validator::make($request->all(), [
            'txidRealizowanejWyplatyBtc' => 'required|alpha_num|min:1|max:255',
        ]);

        if ($walidator->fails()) {
            if ($walidator->errors()->has('txidRealizowanejWyplatyBtc')) {
                return back()->with('blad', 'Nieprawidłowe TxID');
            }
        }

        $txidWyplatyBtc = trim($request->txidRealizowanejWyplatyBtc);

        $bitcoinApi = new BitcoinApi();

        $informacjeOTransakcji = $bitcoinApi->informacjeOTransakcji($txidWyplatyBtc);

        if ($informacjeOTransakcji) {

            DB::beginTransaction();

            try {

                $wyplataBtc = WyplataBtc::where('id', $wyplataBtc->id)->lockForUpdate()->firstOrFail();

                if ($wyplataBtc->status === "Realizowana") {

                    $adresPortfelaDoWyplaty = $wyplataBtc->adres_portfela_do_wyplaty;
                    if (WyplataBtc::where('adres_portfela_do_wyplaty', $adresPortfelaDoWyplaty)->whereRelation('transakcjaBlockchain', 'txid', $txidWyplatyBtc)->exists()) {
                        DB::rollback();
                        Log::channel('admin')->error('Istnieje już wypłata BTC z takim adresem portfela odbiorcy oraz TXID', ['txId' => $txidWyplatyBtc, 'portfelOdbiorcy' => $adresPortfelaDoWyplaty]);
                        return back()->with('blad', 'Istnieje już wypłata z takim adresem portfela oraz TXID');
                    }

                    $domyslneParametryLogow = [
                        'idWyplatyBtc' => $wyplataBtc->id,
                        'txId' => $txidWyplatyBtc];

                    Log::channel('admin')->withContext($domyslneParametryLogow);

                    Log::channel('admin')->info('Rozpoczęto realizację wypłaty BTC');

                    $kwotaWyplatyBtc = $wyplataBtc->kwota_btc;
                    $wyplaconaKwotaBtcDlaTransakcji = 0.00000000;

                    $wszystkiePrzelewy = $informacjeOTransakcji["details"];
                    $czyOdnalezionoTakiAdresPortfela = false;
                    foreach ($wszystkiePrzelewy as $przelew) {

                        if ($przelew["category"] === "send" && $przelew["address"] === $adresPortfelaDoWyplaty) {
                            $czyOdnalezionoTakiAdresPortfela = true;
                            $wyplaconaKwotaBtcDlaTransakcji += abs($przelew["amount"]);
                        }
                    }

                    if (!$czyOdnalezionoTakiAdresPortfela) {
                        DB::rollback();
                        Log::channel('admin')->error('Błąd podczas realizacji wypłaty BTC', ['adresPortfelaDoWyplaty' => $adresPortfelaDoWyplaty]);
                        return back()->with('blad', 'Nie wykryto adresu odbiorcy w transakcji o takim TxID');
                    }

                    if ($this->pomocnikLiczbowy->formatujLiczbe($wyplaconaKwotaBtcDlaTransakcji, 8) !== $kwotaWyplatyBtc) {
                        DB::rollback();
                        Log::channel('admin')->error('Błąd podczas realizacji wypłaty BTC', ['wyplaconaKwotaBtcDlaTransakcji' => $wyplaconaKwotaBtcDlaTransakcji, 'kwotaWyplatyBtc' => $kwotaWyplatyBtc]);
                        return back()->with('blad', 'Kwota wypłaty nie zgadza się z kwotą w transakcji o takim TxID');
                    }

                    // Sprawdź, czy jest takie txid, czy w wysłanych znajduje się taka wypłata i czy wysłano odpowiednią kwotę. Jeśli wszystkie warunki są spełnione, dodaj txid do tabeli i zmień status

                    $transakcjaBlockchainWyplaty = TransakcjaBlockchain::where("txid", $txidWyplatyBtc)->first();
                    $liczbaPotwierdzenTransakcji = $informacjeOTransakcji["confirmations"];

                    if (!$transakcjaBlockchainWyplaty) {
                        $transakcjaBlockchainWyplaty = new TransakcjaBlockchain();
                        $transakcjaBlockchainWyplaty->txid = $txidWyplatyBtc;
                        $transakcjaBlockchainWyplaty->liczba_potwierdzen = $liczbaPotwierdzenTransakcji;
                        $transakcjaBlockchainWyplaty->oplata_btc = abs($informacjeOTransakcji["fee"]);
                        $transakcjaBlockchainWyplaty->save();
                        if (!$transakcjaBlockchainWyplaty) {
                            DB::rollback();
                            Log::channel('admin')->error('Błąd podczas realizacji wypłaty BTC');
                            return back()->with('blad', 'Wystąpił błąd podczas zapisywania nowej transakcji blockchain');
                        }
                    }

                    $wyplataBtc->transakcjaBlockchain()->associate($transakcjaBlockchainWyplaty);
                    $wyplataBtc->status = "Wysłana";
                    $zapisanoWyplateBtc = $wyplataBtc->save();

                    if (!$zapisanoWyplateBtc) {

                        // Błąd podczas zapisywania realizowanej wypłaty
                        DB::rollback();
                        Log::channel('admin')->error('Błąd podczas zapisywania realizowanej wypłaty BTC', ['realizowanaWyplataBtc' => $wyplataBtc]);

                        return back()->with('blad', 'Wystąpił błąd podczas realizowania wypłaty BTC');

                    }

                    DB::commit();
                    Log::channel('admin')->info('Wypłata BTC została przekazana do realizacji');
                    return back()->with('sukces', 'Wypłata BTC została przekazana do realizacji');

                } else {
                    DB::rollback();
                    Log::channel('admin')->error('Próbowano zrealizować wypłatę ze statusem innym niż "Realizowana"');
                    return back()->with('blad', 'Wystąpił błąd podczas realizowania wypłaty BTC');
                }

            } catch (Exception $wyjatek) {
                DB::rollback();
                Log::channel('admin')->critical('Wyjątek przy realizowaniu wypłaty BTC', [
                    'wyjatek' => $wyjatek]);
                return back()->with('blad', 'Wystąpił błąd podczas realizowania wypłaty BTC');
            }

        } else {
            return back()->with('blad', 'Nie wykryto transakcji o podanym TxID');
        }

    }

}
