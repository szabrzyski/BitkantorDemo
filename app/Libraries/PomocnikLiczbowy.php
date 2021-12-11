<?php

namespace App\Libraries;
use Illuminate\Support\Facades\Log;

class PomocnikLiczbowy
{

    // Zaokrąglij wartość w dół po X miejscach po przecinku
    public function zaokraglWDolPoPrzecinku(float $liczba, int $cyfryPoPrzecinku)
    {

        $liczba = $this->formatujLiczbe($liczba, $cyfryPoPrzecinku);
        $liczbaPomniejszajaca = $this->podniesXDoPotegiY(0.1, $cyfryPoPrzecinku, $cyfryPoPrzecinku);
        $liczbaZaokraglona = $this->formatujLiczbe(round(floatval($liczba), $cyfryPoPrzecinku), $cyfryPoPrzecinku);

        if ($this->czyXMniejszeLubRowneY($liczbaZaokraglona, $liczba, $cyfryPoPrzecinku)) {
            $wynik = $liczbaZaokraglona;
        } else {
            $wynik = $this->odejmijYodX($liczbaZaokraglona, $liczbaPomniejszajaca, $cyfryPoPrzecinku);
        }
        return $wynik;

    }
    

    // Zaokrąglij wartość w górę po X miejscach po przecinku
    public function zaokraglWGorePoPrzecinku($liczba, int $cyfryPoPrzecinku)
    {
        $liczba = $this->formatujLiczbe($liczba, $cyfryPoPrzecinku);
        $liczbaPowiekszajaca = $this->podniesXDoPotegiY(0.1, $cyfryPoPrzecinku, $cyfryPoPrzecinku);
        $liczbaZaokraglona = $this->formatujLiczbe(round(floatval($liczba), $cyfryPoPrzecinku), $cyfryPoPrzecinku);

        if ($this->czyXMniejszeOdY($liczbaZaokraglona, $liczba, $cyfryPoPrzecinku)) {
            $wynik = $this->dodajXdoY($liczbaZaokraglona, $liczbaPowiekszajaca, $cyfryPoPrzecinku);
        } else {
            $wynik = $liczbaZaokraglona;
        }
        return $wynik;

    }

    public function formatujLiczbe($liczba, int $cyfryPoPrzecinku, bool $czyAbsolutna = false)
    {
        if ($czyAbsolutna) {
            return number_format(abs($liczba), $cyfryPoPrzecinku, '.', '');
        } else {
            return number_format($liczba, $cyfryPoPrzecinku, '.', '');
        }

    }

    public function czyXWiekszeOdY($x, $y, int $cyfryPoPrzecinku)
    {

        $x = $this->formatujLiczbe($x, $cyfryPoPrzecinku);
        $y = $this->formatujLiczbe($y, $cyfryPoPrzecinku);

        return (bccomp($x, $y, $cyfryPoPrzecinku) === 1);

    }

    public function czyXWiekszeLubRowneY($x, $y, int $cyfryPoPrzecinku)
    {

        $x = $this->formatujLiczbe($x, $cyfryPoPrzecinku);
        $y = $this->formatujLiczbe($y, $cyfryPoPrzecinku);
        $wynik = bccomp($x, $y, $cyfryPoPrzecinku);

        return ($wynik === 1 || $wynik === 0);

    }

    public function czyXMniejszeLubRowneY($x, $y, int $cyfryPoPrzecinku)
    {

        $x = $this->formatujLiczbe($x, $cyfryPoPrzecinku);
        $y = $this->formatujLiczbe($y, $cyfryPoPrzecinku);
        $wynik = bccomp($x, $y, $cyfryPoPrzecinku);

        return ($wynik === -1 || $wynik === 0);

    }

    public function czyXMniejszeOdY($x, $y, int $cyfryPoPrzecinku)
    {

        $x = $this->formatujLiczbe($x, $cyfryPoPrzecinku);
        $y = $this->formatujLiczbe($y, $cyfryPoPrzecinku);

        return (bccomp($x, $y, $cyfryPoPrzecinku) === -1);

    }

    public function czyXRowneY($x, $y, int $cyfryPoPrzecinku)
    {

        $x = $this->formatujLiczbe($x, $cyfryPoPrzecinku);
        $y = $this->formatujLiczbe($y, $cyfryPoPrzecinku);

        return (bccomp($x, $y, $cyfryPoPrzecinku) === 0);

    }

    public function dodajXdoY($x, $y, int $cyfryPoPrzecinku)
    {

        $x = $this->formatujLiczbe($x, $cyfryPoPrzecinku);
        $y = $this->formatujLiczbe($y, $cyfryPoPrzecinku);

        return bcadd($x, $y, $cyfryPoPrzecinku);

    }

    public function odejmijYodX($x, $y, int $cyfryPoPrzecinku)
    {

        $x = $this->formatujLiczbe($x, $cyfryPoPrzecinku);
        $y = $this->formatujLiczbe($y, $cyfryPoPrzecinku);

        return bcsub($x, $y, $cyfryPoPrzecinku);

    }

    public function pomnozXprzezY($x, $y, int $cyfryPoPrzecinku)
    {

        $x = $this->formatujLiczbe($x, $cyfryPoPrzecinku);
        $y = $this->formatujLiczbe($y, $cyfryPoPrzecinku);

        return bcmul($x, $y, $cyfryPoPrzecinku);

    }

    public function podzielXprzezY($x, $y, int $cyfryPoPrzecinku)
    {

        $x = $this->formatujLiczbe($x, $cyfryPoPrzecinku);
        $y = $this->formatujLiczbe($y, $cyfryPoPrzecinku);

        return bcdiv($x, $y, $cyfryPoPrzecinku);

    }

    public function podniesXDoPotegiY($x, $y, int $cyfryPoPrzecinku)
    {

        $x = $this->formatujLiczbe($x, $cyfryPoPrzecinku);
        $y = $this->formatujLiczbe($y, 0);

        return bcpow($x, $y, $cyfryPoPrzecinku);

    }

}
