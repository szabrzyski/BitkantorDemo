<?php

namespace App\Http\Controllers;

use App\Libraries\PomocnikLiczbowy;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class SaldoPlnController extends Controller
{

    public function __construct()
    {
        $this->pomocnikLiczbowy = new PomocnikLiczbowy();
    }

    public function saldoPln(Request $request)
    {

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
                "tytul_przelewu" => $wyplataPlnUzytkownika['tytul_przelewu'],
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
        return view('saldoPln')->with('uzytkownik', $uzytkownik)->with('wplatyOrazWyplatyUzytkownika', $wplatyOrazWyplatyUzytkownikaPorcja)->with('wyniki', $wyniki);

    }

}
