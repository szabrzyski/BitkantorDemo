<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class HistoriaTransakcjiController extends Controller
{

    public function historiaTransakcji(Request $request)
    {

        $uzytkownik = $request->user()->load('transakcjeJakoWystawiajacy', 'transakcjeJakoPrzyjmujacy');

        $transakcjeUzytkownikaPolaczone = $uzytkownik->transakcjeJakoPrzyjmujacy->merge($uzytkownik->transakcjeJakoWystawiajacy)->sortByDesc('created_at');
        // Paginacja
        $liczbaWynikow = 15;
        if ($request->page) {
            $strona = $request->page;
        } else {
            $strona = 1;
        }

        $transakcjeUzytkownikaPorcja = array_slice($transakcjeUzytkownikaPolaczone->toArray(), ($strona * $liczbaWynikow) - $liczbaWynikow, $liczbaWynikow);

        foreach ($transakcjeUzytkownikaPorcja as $index => $transakcja) {

            if ($transakcja['wystawiajacy_id'] === $uzytkownik->id) {
                $transakcjeUzytkownikaPorcja[$index]['typ_transakcji'] = Str::ucfirst($transakcja['typ_oferty_wystawianej']);

                if ($transakcja['typ_oferty_wystawianej'] === "Zakup") {
                    $prowizja = $transakcja['prowizja_wystawiajacego_btc'];
                    if ($prowizja) {
                        $transakcjeUzytkownikaPorcja[$index]['prowizja'] = $prowizja . " BTC";
                    }
                } elseif ($transakcja['typ_oferty_wystawianej'] === "Sprzedaż") {
                    $prowizja = $transakcja['prowizja_wystawiajacego_pln'];
                    if ($prowizja) {
                        $transakcjeUzytkownikaPorcja[$index]['prowizja'] = $prowizja . " PLN";
                    }
                }

            } else {
                $transakcjeUzytkownikaPorcja[$index]['typ_transakcji'] = Str::ucfirst($transakcja['typ_oferty_przyjmujacej']);

                if ($transakcja['typ_oferty_przyjmujacej'] === "Zakup") {
                    $prowizja = $transakcja['prowizja_przyjmujacego_btc'];
                    if ($prowizja) {
                        $transakcjeUzytkownikaPorcja[$index]['prowizja'] = $prowizja . " BTC";
                    }
                } elseif ($transakcja['typ_oferty_przyjmujacej'] === "Sprzedaż") {
                    $prowizja = $transakcja['prowizja_przyjmujacego_pln'];
                    if ($prowizja) {
                        $transakcjeUzytkownikaPorcja[$index]['prowizja'] = $prowizja . " PLN";
                    }
                }
            }
            if (!$prowizja) {
                $transakcjeUzytkownikaPorcja[$index]['prowizja'] = "-";
            }
        }
        $transakcjeUzytkownika = collect($transakcjeUzytkownikaPorcja);

        $paginator = new LengthAwarePaginator($transakcjeUzytkownika, count($transakcjeUzytkownikaPolaczone), $liczbaWynikow, $strona);
        $wyniki = $paginator->withPath(url()->current())->appends(request()->except('page'));

        return view('historiaTransakcji')->with('uzytkownik', $uzytkownik)->with('transakcjeUzytkownika', $transakcjeUzytkownikaPorcja)->with('wyniki', $wyniki);

    }

}
