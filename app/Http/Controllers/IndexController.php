<?php

namespace App\Http\Controllers;

use App\Libraries\PomocnikLiczbowy;
use App\Models\Oferta;
use App\Models\Transakcja;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class IndexController extends Controller
{

    public function index(Request $request)
    {

        $uzytkownik = $request->user();

        return view('index')->with('uzytkownik', $uzytkownik);

    }

    public function pobierzOstatnieTransakcje(Request $request)
    {

        $transakcje = Transakcja::latest()->take(100)->get();
        return response()->json([
            'transakcje' => $transakcje,
        ], 200);

    }

    public function pobierzOferty(Request $request)
    {

        $uzytkownik = $request->user();
        $aktywneOferty = Oferta::where('status', 'Aktywna')->get();
        $pomocnikLiczbowy = new PomocnikLiczbowy();
        $ofertyZakupu = $ofertySprzedazy = $ofertyUzytkownika = array();

        foreach ($aktywneOferty as $oferta) {

            if ($uzytkownik) {

                if ($oferta->wystawiajacy_id === $uzytkownik->id) {
                    $oferta->pozostala_kwota_pln = $pomocnikLiczbowy->formatujLiczbe($pomocnikLiczbowy->pomnozXprzezY($oferta->kurs,$oferta->pozostala_kwota_btc,10), 2);
                    $ofertyUzytkownika[] = $oferta;
                }
            }
            if ($oferta->typ === "Zakup") {

                if (!Arr::exists($ofertyZakupu, $oferta->kurs)) {
                    $ofertyZakupu[$oferta->kurs]['pozostala_kwota_btc'] = 0;
                    $ofertyZakupu[$oferta->kurs]['pozostala_kwota_pln'] = 0;
                }
                
                $ofertyZakupu[$oferta->kurs]['pozostala_kwota_btc'] = $pomocnikLiczbowy->dodajXdoY($ofertyZakupu[$oferta->kurs]['pozostala_kwota_btc'],$oferta->pozostala_kwota_btc,8);
                

                $ofertyZakupu[$oferta->kurs]['pozostala_kwota_pln'] = $pomocnikLiczbowy->dodajXdoY($ofertyZakupu[$oferta->kurs]['pozostala_kwota_pln'],$pomocnikLiczbowy->pomnozXprzezY($oferta->kurs,$oferta->pozostala_kwota_btc,10),2);
                

            } elseif ($oferta->typ === "Sprzedaż") {

                if (!Arr::exists($ofertySprzedazy, $oferta->kurs)) {
                    $ofertySprzedazy[$oferta->kurs]['pozostala_kwota_btc'] = 0;
                    $ofertySprzedazy[$oferta->kurs]['pozostala_kwota_pln'] = 0;
                }


                $ofertySprzedazy[$oferta->kurs]['pozostala_kwota_btc'] = $pomocnikLiczbowy->dodajXdoY($ofertySprzedazy[$oferta->kurs]['pozostala_kwota_btc'],$oferta->pozostala_kwota_btc,8);

                $ofertySprzedazy[$oferta->kurs]['pozostala_kwota_pln'] =  $pomocnikLiczbowy->dodajXdoY($ofertySprzedazy[$oferta->kurs]['pozostala_kwota_pln'],$pomocnikLiczbowy->pomnozXprzezY($oferta->kurs,$oferta->pozostala_kwota_btc,10),2);
            }

        }

        krsort($ofertyZakupu);
        ksort($ofertySprzedazy);
        $ostatnie100OfertZakupu = array_slice($ofertyZakupu, 0, 100);
        $ostatnie100OfertSprzedazy = array_slice($ofertySprzedazy, 0, 100);

        return response()->json([
            'ofertyZakupu' => $ostatnie100OfertZakupu,
            'ofertySprzedazy' => $ostatnie100OfertSprzedazy,
            'ofertyUzytkownika' => $ofertyUzytkownika,
        ], 200);

    }

    public function pobierzStatystyki(Request $request)
    {
        $ostatnie24h = Carbon::now()->subDay();
        $aktualnyKurs = Oferta::where('status', 'Aktywna')->where('typ', 'Sprzedaż')->min('kurs');
        $pozostaleKursyOrazWolumen = DB::table('transakcje')->selectRaw('MIN(kurs) AS najnizszyKurs24h, MAX(kurs) AS najwyzszyKurs24h, SUM(kwota_btc) AS wolumen24h')->where('created_at', '>=', $ostatnie24h)->first();
        return response()->json([
            'aktualnyKurs' => $aktualnyKurs,
            'najnizszyKurs24h' => $pozostaleKursyOrazWolumen->najnizszyKurs24h,
            'najwyzszyKurs24h' => $pozostaleKursyOrazWolumen->najwyzszyKurs24h,
            'wolumen24h' => $pozostaleKursyOrazWolumen->wolumen24h,
        ], 200);
    }

    public function pobierzSaldoOrazProwizje(Request $request)
    {

        $uzytkownik = $request->user();

        if ($uzytkownik) {
            $saldoPlnUzytkownika = $uzytkownik->saldo_pln;
            $saldoBtcUzytkownika = $uzytkownik->saldo_btc;
            if ($uzytkownik->tryb_rozliczania === 'Prowizja') {
                $prowizjaUzytkownikaProcent = $uzytkownik->prowizjaProcent();
            } else {
                $prowizjaUzytkownikaProcent = 0;
            }

            return response()->json([
                'saldoPlnUzytkownika' => $saldoPlnUzytkownika,
                'saldoBtcUzytkownika' => $saldoBtcUzytkownika,
                'prowizjaUzytkownikaProcent' => $prowizjaUzytkownikaProcent,
            ], 200);

        } else {
            return response()->json([], 204);
        }

        $ostatnie24h = Carbon::now()->subDay();
        $aktualnyKurs = Oferta::where('status', 'Aktywna')->where('typ', 'Sprzedaż')->min('kurs');
        $pozostaleKursyOrazWolumen = DB::table('transakcje')->selectRaw('MIN(kurs) AS najnizszyKurs24h, MAX(kurs) AS najwyzszyKurs24h, SUM(kwota_btc) AS wolumen24h')->where('created_at', '>=', $ostatnie24h)->first();
        return response()->json([
            'aktualnyKurs' => $aktualnyKurs,
            'najnizszyKurs24h' => $pozostaleKursyOrazWolumen->najnizszyKurs24h,
            'najwyzszyKurs24h' => $pozostaleKursyOrazWolumen->najwyzszyKurs24h,
            'wolumen24h' => $pozostaleKursyOrazWolumen->wolumen24h,
        ], 200);
    }

}
