const saldoBtc = {
  data() {
    return {
      // Prowizja za wypłatę
      prowizjaZaWyplateBtc: prowizjaZaWyplateBtc,
      // Minimalna wartość wypłaty za wypłatę
      minimalnaWyplataBtc: minimalnaKwotaWyplatyBtc,
      // Blok wypłaty
      kwotaWyplatyBtc: '',
      adresPortfelaDoWyplaty: '',
      btcDoPobrania: 0,
      // Komunikat toastowy
      tekstKomunikatu: '',
      // Komunikat wyskakującego okienka z potwierdzeniem akcji
      tekstPotwierdzeniaAkcji: '',
      // Wywoływanie funkcji po potwierdzeniu akcji
      nazwaWywolywanejFunkcji: '',
      parametrWywolywanejFunkcji: '',
      // Używane do blokowania przycisku wypłaty
      trwaWyplacanieBtc: false,
      // Pomocnik liczbowy
      pomocnikLiczbowy: new PomocnikLiczbowy()
    }
  },
  computed: {
    saldoBtc() {
      return Number(this.$refs.saldoBtc.innerHTML);
    },
    btcDoPobraniaSformatowane() {
      return Number(this.btcDoPobrania).toFixed(8);
    },
    komunikatToastowy() {
      return this.$refs.komunikatToastowy;
    },
    potwierdzenieAkcji() {
      return this.$refs.potwierdzenieAkcji;
    },
    adresPortfelaDoWplaty() {
      return this.$refs.adresPortfelaDoWplaty.value;
    }
  },
  created() {
  },
  methods: {
    walidujOrazWyslijFormularz(zdarzenie) {

      if (this.walidujFormularz()) {
        this.trwaWyplacanieBtc = true;
      } else {
        zdarzenie.preventDefault();
      }
    },
    walidujFormularz() {

      let kwotaBtc = this.kwotaWyplatyBtc;
      let saldoBtc = this.saldoBtc;

      if (isNaN(kwotaBtc)) {
        this.pokazKomunikat("Nieprawidłowa kwota wypłaty");
        return false;
      }
      if (saldoBtc < Number(kwotaBtc)) {
        this.pokazKomunikat("Twoje saldo jest zbyt niskie");
        return false;
      }
      if (Number(kwotaBtc) < this.minimalnaWyplataBtc) {
        this.pokazKomunikat("Minimalna kwota wypłaty to " + this.minimalnaWyplataBtc + " BTC");
        return false;
      }

      if (!this.walidujAdresPortfelaDoWyplaty()) {
        return false;
      }

      return true;

    },
    // Wyliczanie BTC do pobrania i formatowanie pól w formularzu wypłaty
    wyliczBtcDoPobrania() {

      let kwotaWyplatyBtcSformatowana = this.kwotaWyplatyBtc.replace(',', '.').replace(/[^0-9*\.?0-9*]/, '').split('.').slice(0, 2).join('.');

      this.kwotaWyplatyBtc = (kwotaWyplatyBtcSformatowana.indexOf(".") >= 0) ? (kwotaWyplatyBtcSformatowana.substr(0, kwotaWyplatyBtcSformatowana.indexOf(".")) + kwotaWyplatyBtcSformatowana.substr(kwotaWyplatyBtcSformatowana.indexOf("."), 9)) : kwotaWyplatyBtcSformatowana;
      let btcDoPobrania = this.pomocnikLiczbowy.zaokraglWDolPoPrzecinku(Number(this.kwotaWyplatyBtc) + this.prowizjaZaWyplateBtc, 8);

      if (isNaN(btcDoPobrania) || Number(this.kwotaWyplatyBtc) < this.minimalnaWyplataBtc) {
        this.btcDoPobrania = 0;
      } else {
        this.btcDoPobrania = btcDoPobrania;

      }

    },
    // Formatuj wypisywany adres portfela do wypłaty
    formatujAdresPortfelaDoWyplaty() {

      let adresPortfelaDoWyplatySformatowany = this.adresPortfelaDoWyplaty.replace(/[^a-zA-Z0-9]/, '');
      this.adresPortfelaDoWyplaty = adresPortfelaDoWyplatySformatowany;

    },
    // Waliduj adres do wypłaty
    walidujAdresPortfelaDoWyplaty() {

      let dopuszczalnyWzor = /^[0-9a-zA-Z]+$/;
      if (!this.adresPortfelaDoWyplaty.match(dopuszczalnyWzor)) {
        this.pokazKomunikat("Nieprawidłowy format adresu portfela");
        return false;
      }
      if (this.adresPortfelaDoWyplaty === this.adresPortfelaDoWplaty) {
        this.pokazKomunikat("Nieprawidłowy adres portfela");
        return false;
      }

      return true;

    },
    // Kopiuje przekazany tekst do schowka
    skopiujDoSchowka(tekst) {
      navigator.clipboard.writeText(tekst);
      this.pokazKomunikat('Skopiowano do schowka');
    },
    // Anuluje wypłatę BTC
    anulujWyplateBtc(wyplataBtc) {
      this.$refs.formularzAnulowaniaWyplatyBtc.action = "/saldoBtc/anulujWyplate/" + wyplataBtc;
      this.$refs.formularzAnulowaniaWyplatyBtc.submit();
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
    potwierdzenieAnulowaniaWyplatyBtc(idWyplatyBtc) {
      this.pokazPotwierdzenieAkcji('Czy na pewno chcesz anulować wypłatę?', 'anulujWyplateBtc', idWyplatyBtc);
    },
    // Kup lub sprzedaj bitcoiny do dostępnego salda
    wyplacWszystko() {
      if (this.saldoBtc > 0) {
        this.kwotaWyplatyBtc = this.saldoBtc.toFixed(8);
        this.wyliczBtcDoPobrania();
      }
    }
  }
}

Vue.createApp(saldoBtc).mount('#saldoBtc');