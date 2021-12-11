<?php

namespace App\Http\Controllers;

use App\Libraries\PomocnikLiczbowy;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SaldoBtcController extends Controller
{

    public function __construct()
    {
        $this->pomocnikLiczbowy = new PomocnikLiczbowy();
    }

    public function saldoBtc(Request $request)
    {

        $uzytkownik = $request->user()->load('wplatyBtc.transakcjaBlockchain', 'wyplatyBtc.transakcjaBlockchain');

        $wplatyBtcUzytkownika = $uzytkownik->wplatyBtc->toArray();
        $wyplatyBtcUzytkownika = $uzytkownik->wyplatyBtc->toArray();

        $wplatyOrazWyplatyUzytkownika = array();

        foreach ($wplatyBtcUzytkownika as $wplataBtcUzytkownika) {
            $wplataBtc = array(
                "id" => $wplataBtcUzytkownika['id'],
                "typ" => "Wpłata",
                "tx_id" => $wplataBtcUzytkownika['transakcja_blockchain']["txid"],
                "kwota_btc" => $this->pomocnikLiczbowy->formatujLiczbe($wplataBtcUzytkownika['kwota_btc'], 8),
                "adres_docelowy" => $wplataBtcUzytkownika['adres_portfela_odbiorcy'],
                "status" => $wplataBtcUzytkownika['status'],
                "prowizja" => "-",
                "created_at" => $wplataBtcUzytkownika['created_at'],
              //  "updated_at" => $wplataBtcUzytkownika['updated_at'],
            );
            $wplatyOrazWyplatyUzytkownika[] = $wplataBtc;
        }

        foreach ($wyplatyBtcUzytkownika as $wyplataBtcUzytkownika) {
            $wyplataBtc = array(
                "id" => $wyplataBtcUzytkownika['id'],
                "typ" => "Wypłata",
                "tx_id" => ($wyplataBtcUzytkownika['transakcja_blockchain']) ? $wyplataBtcUzytkownika['transakcja_blockchain']["txid"] : null,
                "kwota_btc" => $this->pomocnikLiczbowy->formatujLiczbe($wyplataBtcUzytkownika['kwota_btc'], 8),
                "adres_docelowy" => $wyplataBtcUzytkownika['adres_portfela_do_wyplaty'],
                "status" => $wyplataBtcUzytkownika['status'],
                "prowizja" => $this->pomocnikLiczbowy->formatujLiczbe($wyplataBtcUzytkownika['prowizja_btc'], 8),
                "created_at" => $wyplataBtcUzytkownika['created_at'],
              //  "updated_at" => $wyplataBtcUzytkownika['updated_at'],
            );
            $wplatyOrazWyplatyUzytkownika[] = $wyplataBtc;
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
        return view('saldoBtc')->with('uzytkownik', $uzytkownik)->with('wplatyOrazWyplatyUzytkownika', $wplatyOrazWyplatyUzytkownikaPorcja)->with('wyniki', $wyniki);

    }

}
