<?php

namespace App\Libraries;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BitcoinApi
{

    private int $maksymalnyCzasOdpowiedzi = 60;

    public function transakcjeDlaAdresow()
    {

        $wynik = $this->wykonajZapytanie("listreceivedbyaddress", ["minconf" => 6]);

        if (is_array($wynik)) {
            return $wynik;
        } else {
            return null;
        }

    }

    public function listaOstatnichTransakcjiPrzychodzacych($liczbaOstatnichTransakcji = 1000)
    {

        $wynik = $this->wykonajZapytanie("listtransactions", ["label" => "adresy_uzytkownikow", "count" => $liczbaOstatnichTransakcji]);

        if (is_array($wynik)) {
            return $wynik;
        } else {
            return null;
        }

    }

    public function listaOstatnichTransakcji($liczbaOstatnichTransakcji = 1000)
    {

        $wynik = $this->wykonajZapytanie("listtransactions", ["count" => $liczbaOstatnichTransakcji]);

        if (is_array($wynik)) {
            return $wynik;
        } else {
            return null;
        }

    }

    public function informacjeOTransakcji($txid)
    {

        $wynik = $this->wykonajZapytanie("gettransaction", ["txid" => $txid]);

        if (is_array($wynik) && Arr::exists($wynik, "txid")) {
            return $wynik;
        } else {
            return null;
        }

    }

    public function weryfikujAdresPortfela($adresPortfela)
    {

        $wynik = $this->wykonajZapytanie("validateaddress", ["address" => $adresPortfela]);

        if (is_array($wynik) && Arr::exists($wynik, 'isvalid')) {
            return $wynik['isvalid'];
        } else {
            return null;
        }

    }

    // Wysyła zapytanie do bitcoind
    public function wykonajZapytanie(string $metoda, ?array $parametry = null)
    {

        try {

            $domyslneParametryLogow = [
                'metoda' => $metoda,
                'parametry' => $parametry];

            Log::channel('bledyBitcoinApi')->withContext($domyslneParametryLogow);

            $odpowiedz = Http::withBasicAuth('bitkantor', 'xxxxxxxxx')->timeout($this->maksymalnyCzasOdpowiedzi)->post('http://localhost:18332/', [
                'method' => $metoda,
                'params' => $parametry,
            ]);

            if ($odpowiedz->successful()) {

                if (Arr::exists($odpowiedz, 'result')) {
                    return $odpowiedz['result'];
                } else {

                    Log::channel('bledyBitcoinApi')->error('Odpowiedź zwróciła sukces, ale nie ma rezultatu');
                    return null;
                }

            } else {

                if (Arr::exists($odpowiedz, 'error') && $odpowiedz['error'] != null) {
                    Log::channel('bledyBitcoinApi')->error('Odpowiedź zwróciła błąd z wiadomością', [
                        'blad' => $odpowiedz['error']]);
                } else {
                    Log::channel('bledyBitcoinApi')->error('Odpowiedź zwróciła sukces, ale nie ma rezultatu');
                }
                return null;
            }

        } catch (Exception $wyjatek) {
            Log::channel('wyjatki')->critical('Wyjątek podczas łączenia z API Bitcoin', [
                'wyjatek' => $wyjatek]);
            return null;
        }

    }

}
