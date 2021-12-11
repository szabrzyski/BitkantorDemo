<?php

namespace App\Http\Controllers;

use App\Libraries\BitcoinApi;
use App\Models\User;
use App\Models\Oferta;
use App\Models\TransakcjaBlockchain;
use App\Models\WyplataBtc;
use App\Models\WyplataPln;
use App\Models\WplataBtc;
use Illuminate\Support\Arr;
use App\Libraries\PomocnikLiczbowy;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestController extends Controller
{

    public function __construct()
    {
        $this->pomocnikLiczbowy = new PomocnikLiczbowy();
    }

    public function test(Request $request, User $uzytkownik)
    {

 


        $a = number_format(0.000000010,8);
        $b = 0.000000010;
 
      //  dd($a);

    //     $ostatnioWykryteWplatyBtc = WplataBtc::with("transakcjaBlockchain")->latest()->limit(1000)->get();

    //    // dd($ostatnioWykryteWplatyBtc);
      

    //     $czyWplataJestJuzWSystemie = $ostatnioWykryteWplatyBtc->contains(function ($wartosc, $klucz) {
    //         //  $klucz === "" && $wartosc === "";
    //         return $wartosc->adres_portfela_odbiorcy === "tb1qq26dvkf9nnxfs3hw0n78ld6nxduf8y7almus6ux" && $wartosc->transakcjaBlockchain->txid == "5286e3847b0d6f6e438791f2a6e7af8fca86606b9c1b5a43d2a34c4785b4b682";
    //     });

        // dd($czyWplataJestJuzWSystemie);
        // $uzytkownik2 = User::where('id',$request->user()->id)->lockForUpdate()->firstOrFail();
        // dd($uzytkownik2);
    
    //     $bitcoinApi = new BitcoinApi();
    //     // $odpowiedzBitcoinApi = $bitcoinApi->wykonajZapytanie("getnewaddress",["address_type" => "bech32"]);
    //    // $test = $bitcoinApi->listaTransakcji();
    //     // dd($test[0]["txids"]);
    //     $test = $bitcoinApi->informacjeOTransakcji("cd6f99b76428c8c8525135fddf1e18380378113a944c5cf52cd9c851eecc55c8");
    //   //$test = $bitcoinApi->listaOstatnichTransakcji();
    //     dd($test);
    //     // $odpowiedzBitcoinApi = $bitcoinApi->wykonajZapytanie("listreceivedbyaddress",["address_filter" => "tb1qsdg0m94zw0qt3xv6w4dnjdltflawegwje6rge3"]);
    //     $odpowiedzBitcoinApi = $bitcoinApi->wykonajZapytanie("listtransactions", []);
    //     // $odpowiedzBitcoinApi = $bitcoinApi->wykonajZapytanie("createwallet",["wallet_name" => "bitkantortestnet"]);
    //     if ($odpowiedzBitcoinApi) {
    //         dd($odpowiedzBitcoinApi);
    //     } else {
    //         dd("nie ok");
    //     }

        // return view('test');



        $uzytkownik = $request->user()->load('wplatyPln', 'wyplatyPln');

        $wplatyPlnUzytkownika = $uzytkownik->wplatyPln->toArray();
        $wyplatyPlnUzytkownika = $uzytkownik->wyplatyPln->toArray();

        $wplatyOrazWyplatyUzytkownika = array();

        foreach ($wplatyPlnUzytkownika as $wplataPlnUzytkownika) {
            $wplataPln = array(
                "id" => $wplataPlnUzytkownika['id'],
                "typ" => "Wpłata",
                "tytul_przelewu" => $wplataPlnUzytkownika['tytul_przelewu'],
                "kwota_pln" => $this->pomocnikLiczbowy->formatujLiczbe($wplataPlnUzytkownika['kwota_pln'], 2),
                "konto_bankowe" => $wplataPlnUzytkownika['konto_bankowe_nadawcy'],
                "status" => "",
                "prowizja" => "-",
                "created_at" => $wplataPlnUzytkownika['created_at'],
            );
            $wplatyOrazWyplatyUzytkownika[] = $wplataPln;
        }

        foreach ($wyplatyPlnUzytkownika as $wyplataPlnUzytkownika) {
            $wyplataPln = array(
                "id" => $wyplataPlnUzytkownika['id'],
                "typ" => "Wypłata",
                "tytul_przelewu" => $wplataPlnUzytkownika['tytul_przelewu'],
                "kwota_pln" => $this->pomocnikLiczbowy->formatujLiczbe($wyplataPlnUzytkownika['kwota_pln'], 2),
                "konto_bankowe" => $wyplataPlnUzytkownika['konto_bankowe_odbiorcy'],
                "status" => $wyplataPlnUzytkownika['status'],
                "prowizja" => $this->pomocnikLiczbowy->formatujLiczbe($wyplataPlnUzytkownika['prowizja_pln'], 2),
                "created_at" => $wyplataPlnUzytkownika['created_at'],
            );
            $wplatyOrazWyplatyUzytkownika[] = $wyplataPln;
        }

        $wplatyOrazWyplatyUzytkownikaPosortowane = Arr::sort($wplatyOrazWyplatyUzytkownika, function ($kluczSortujacy) {
            return $kluczSortujacy['created_at'];
        });
        krsort($wplatyOrazWyplatyUzytkownikaPosortowane);

        // Paginacja
        $liczbaWynikow = 15;
        if ($request->page) {
            $strona = $request->page;
        } else {
            $strona = 1;
        }

        $wplatyOrazWyplatyUzytkownikaPorcja = array_slice($wplatyOrazWyplatyUzytkownikaPosortowane, ($strona * $liczbaWynikow) - $liczbaWynikow, $liczbaWynikow);

        $wplatyOrazWyplatyUzytkownika = collect($wplatyOrazWyplatyUzytkownikaPorcja);

        $paginator = new LengthAwarePaginator($wplatyOrazWyplatyUzytkownika, count($wplatyOrazWyplatyUzytkownikaPosortowane), $liczbaWynikow, $strona);
        $wyniki = $paginator->withPath(url()->current())->appends(request()->except('page'));
        return view('test')->with('uzytkownik', $uzytkownik)->with('wplatyOrazWyplatyUzytkownika', $wplatyOrazWyplatyUzytkownikaPorcja)->with('wyniki', $wyniki);




    }

    public function test2(Request $request, User $uzytkownik)
    {



        $uzytkownik2 = User::where('id',1)->first();
        dd($uzytkownik2);


        $a = 100;
        $b = null;
        dd($a-$b);
        $bitcoinApi = new BitcoinApi();
        // $odpowiedzBitcoinApi = $bitcoinApi->wykonajZapytanie("getnewaddress",["address_type" => "bech32"]);
        $test = $bitcoinApi->listaTransakcji();
        // dd($test[0]["txids"]);
        //$test = $bitcoinApi->informacjeOTransakcji("b0fb894a722fda6550a8025121084ea750fbbb58d3499e3335dcfc85cb576598");
        dd($test);
        // $odpowiedzBitcoinApi = $bitcoinApi->wykonajZapytanie("listreceivedbyaddress",["address_filter" => "tb1qsdg0m94zw0qt3xv6w4dnjdltflawegwje6rge3"]);
        $odpowiedzBitcoinApi = $bitcoinApi->wykonajZapytanie("listtransactions", []);
        // $odpowiedzBitcoinApi = $bitcoinApi->wykonajZapytanie("createwallet",["wallet_name" => "bitkantortestnet"]);
        if ($odpowiedzBitcoinApi) {
            dd($odpowiedzBitcoinApi);
        } else {
            dd("nie ok");
        }

        return view('test');
    }

    public function testAxios(Request $request)
    {

        return response()->json(['komunikat' => 'Wystąpił błąd podczas anulowania oferty'], 520);

    }

}
