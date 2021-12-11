<?php

namespace App\Libraries;

class PomocnikLiczbowy
{

    // Zaokrąglij wartość w dół po X miejscach po przecinku
    public function zaokraglWDolPoPrzecinku(float $liczba, int $cyfryPoPrzecinku)
    {

        $zaokroglanaLiczba = round($liczba, $cyfryPoPrzecinku);

        if ($zaokroglanaLiczba > $liczba) {

            $odejmij = 1 / (pow(10, $cyfryPoPrzecinku));
            $zaokroglanaLiczba = $zaokroglanaLiczba - $odejmij;
        }

        return $this->formatujLiczbe($zaokroglanaLiczba, $cyfryPoPrzecinku);

    }

    // Zaokrąglij wartość w górę po X miejscach po przecinku
    public function zaokraglWGorePoPrzecinku(float $liczba, int $cyfryPoPrzecinku)
    {

        $zaokroglanaLiczba = round($liczba, $cyfryPoPrzecinku);

        if ($zaokroglanaLiczba < $liczba) {

            $dodaj = 1 / (pow(10, $cyfryPoPrzecinku));
            $zaokroglanaLiczba = $zaokroglanaLiczba + $dodaj;
        }

        return $this->formatujLiczbe($zaokroglanaLiczba, $cyfryPoPrzecinku);

    }

    public function formatujLiczbe(float $liczba, int $cyfryPoPrzecinku)
    {
        return number_format($liczba, $cyfryPoPrzecinku, '.', '');
    }

}
