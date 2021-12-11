class PomocnikLiczbowy {

    // Zaokrąglij wartość w dół po X miejscach po przecinku
    zaokraglWDolPoPrzecinku(liczba, cyfryPoPrzecinku) {

        let liczbaPomniejszajaca = Math.pow(0.1, cyfryPoPrzecinku);
        let liczbaZaokraglona = this.formatujLiczbe(liczba, cyfryPoPrzecinku);
        if (liczbaZaokraglona <= liczba) {
            var wynik = liczbaZaokraglona;
        } else {
            var wynik = liczbaZaokraglona - liczbaPomniejszajaca;
        }
        return this.formatujLiczbe(wynik, cyfryPoPrzecinku);
    }

    // Zaokrąglij wartość w górę po X miejscach po przecinku
    zaokraglWGorePoPrzecinku(liczba, cyfryPoPrzecinku) {

        let liczbaPowiekszajaca = Math.pow(0.1, cyfryPoPrzecinku);
        let liczbaZaokraglona = this.formatujLiczbe(liczba, cyfryPoPrzecinku);
        if (liczbaZaokraglona < liczba) {
            var wynik = liczbaZaokraglona + liczbaPowiekszajaca;
        } else {
            var wynik = liczbaZaokraglona;
        }

        return this.formatujLiczbe(wynik, cyfryPoPrzecinku);

    }

    formatujLiczbe(liczba, cyfryPoPrzecinku) {
        return Number(liczba).toFixed(cyfryPoPrzecinku);
    }


}