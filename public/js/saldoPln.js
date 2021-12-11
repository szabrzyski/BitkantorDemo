const saldoPln = {
  data() {
    return {
      // Prowizja za wypłatę
      prowizjaZaWyplatePln: prowizjaZaWyplatePln,
      // Minimalna wartość wypłaty za wypłatę
      minimalnaWyplataPln: minimalnaKwotaWyplatyPln,
      // Blok wypłaty
      kwotaWyplatyPln: '',
      numerKontaDoWyplaty: '',
      plnDoPobrania: 0,
      // Komunikat toastowy
      tekstKomunikatu: '',
      // Komunikat wyskakującego okienka z potwierdzeniem akcji
      tekstPotwierdzeniaAkcji: '',
      // Wywoływanie funkcji po potwierdzeniu akcji
      nazwaWywolywanejFunkcji: '',
      parametrWywolywanejFunkcji: '',
      // Używane do blokowania przycisku wypłaty
      trwaWyplacaniePln: false,
      // Pomocnik liczbowy
      pomocnikLiczbowy: new PomocnikLiczbowy()
    }
  },
  computed: {
    saldoPln() {
      return Number(this.$refs.saldoPln.innerHTML);
    },
    plnDoPobraniaSformatowane() {
      return Number(this.plnDoPobrania).toFixed(2);
    },
    komunikatToastowy() {
      return this.$refs.komunikatToastowy;
    },
    potwierdzenieAkcji() {
      return this.$refs.potwierdzenieAkcji;
    },
    numerKontaDoWplaty() {
      let numerKontaDoWplaty = this.$refs.numerKontaDoWplaty.value;
      return numerKontaDoWplaty.replace(/\s+/g, '');
    }
  },
  created() {
  },
  methods: {
    walidujOrazWyslijFormularz(zdarzenie) {

      if (this.walidujFormularz()) {
        this.trwaWyplacaniePln = true;
      } else {
        zdarzenie.preventDefault();
      }
    },
    walidujFormularz() {

      let kwotaPln = this.kwotaWyplatyPln;
      let saldoPln = this.saldoPln;

      if (isNaN(kwotaPln)) {
        this.pokazKomunikat("Nieprawidłowa kwota wypłaty");
        return false;
      }
      if (saldoPln < Number(kwotaPln)) {
        this.pokazKomunikat("Twoje saldo jest zbyt niskie");
        return false;
      }
      if (Number(kwotaPln) < this.minimalnaWyplataPln) {
        this.pokazKomunikat("Minimalna kwota wypłaty to " + this.minimalnaWyplataPln + " PLN");
        return false;
      }

      if (!this.walidujNumerKontaDoWyplaty()) {
        return false;
      }

      return true;

    },
    // Wyliczanie PLN do pobrania i formatowanie pól w formularzu wypłaty
    wyliczPlnDoPobrania() {

      let kwotaWyplatyPlnSformatowana = this.kwotaWyplatyPln.replace(',', '.').replace(/[^0-9*\.?0-9*]/, '').split('.').slice(0, 2).join('.');

      this.kwotaWyplatyPln = (kwotaWyplatyPlnSformatowana.indexOf(".") >= 0) ? (kwotaWyplatyPlnSformatowana.substr(0, kwotaWyplatyPlnSformatowana.indexOf(".")) + kwotaWyplatyPlnSformatowana.substr(kwotaWyplatyPlnSformatowana.indexOf("."), 3)) : kwotaWyplatyPlnSformatowana;
      let plnDoPobrania = this.pomocnikLiczbowy.zaokraglWDolPoPrzecinku(Number(this.kwotaWyplatyPln) + this.prowizjaZaWyplatePln, 8);

      if (isNaN(plnDoPobrania) || Number(this.kwotaWyplatyPln) < this.minimalnaWyplataPln) {
        this.plnDoPobrania = 0;
      } else {
        this.plnDoPobrania = plnDoPobrania;

      }

    },
    // Formatuj wypisywany numer konta do wypłaty
    formatujNumerKontaDoWyplaty() {

      let numerKontaDoWyplatySformatowany = this.numerKontaDoWyplaty.replace(/\D/g, '');
      this.numerKontaDoWyplaty = numerKontaDoWyplatySformatowany;

    },
    // Waliduj adres do wypłaty
    walidujNumerKontaDoWyplaty() {

      let dopuszczalnyWzor = /^[0-9]+$/;

      if (!this.numerKontaDoWyplaty.match(dopuszczalnyWzor)) {
        this.pokazKomunikat("Nieprawidłowy numer konta bankowego");
        return false;
      }

      if (this.numerKontaDoWyplaty.length !== 26) {
        this.pokazKomunikat("Numer konta bankowego musi składać się z 26 cyfr");
        return false;
      }

      if (this.numerKontaDoWyplaty === this.numerKontaDoWplaty) {
        this.pokazKomunikat("Nieprawidłowy numer konta bankowego");
        return false;
      }

      return true;

    },
    // Kopiuje przekazany tekst do schowka
    skopiujDoSchowka(tekst) {
      navigator.clipboard.writeText(tekst);
      this.pokazKomunikat('Skopiowano do schowka');
    },
    // Anuluje wypłatę PLN
    anulujWyplatePln(wyplataPln) {
      this.$refs.formularzAnulowaniaWyplatyPln.action = "/saldoPln/anulujWyplate/" + wyplataPln;
      this.$refs.formularzAnulowaniaWyplatyPln.submit();
    },
    // Pokazuje komunikat toastowy (wyskakuje na dole z prawej strony)
    pokazKomunikat(komunikat) {
      this.tekstKomunikatu = komunikat;
      let komunikatToastowy = bootstrap.Toast.getOrCreateInstance(this.komunikatToastowy);
      komunikatToastowy.show();
    },
    // Funkcja wywołująca inną funkcję, używana np. podczas potwierdzania akcji
    wywolajFunkcje(funkcja, parametr = '') {
      this[funkcja](parametr);
    },
    // Pokazuje okno potwierdzenia akcji
    pokazPotwierdzenieAkcji(tekstPotwierdzeniaAkcji, nazwaWywolywanejFunkcji, parametrWywolywanejFunkcji = '') {
      this.nazwaWywolywanejFunkcji = nazwaWywolywanejFunkcji;
      this.tekstPotwierdzeniaAkcji = tekstPotwierdzeniaAkcji;
      this.parametrWywolywanejFunkcji = parametrWywolywanejFunkcji;
      let potwierdzenieAkcji = new bootstrap.Modal(this.potwierdzenieAkcji)
      potwierdzenieAkcji.show();
    },
    potwierdzenieAnulowaniaWyplatyPln(idWyplatyPln) {
      this.pokazPotwierdzenieAkcji('Czy na pewno chcesz anulować wypłatę?', 'anulujWyplatePln', idWyplatyPln);
    },
    // Kup lub sprzedaj bitcoiny do dostępnego salda
    wyplacWszystko() {
      if (this.saldoPln > 0) {
        this.kwotaWyplatyPln = this.saldoPln.toFixed(2);
        this.wyliczPlnDoPobrania();
      }
    }
  }
}

Vue.createApp(saldoPln).mount('#saldoPln');