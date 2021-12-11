const wyplatyBtc = {
  data() {
    return {
      // Komunikat toastowy
      tekstKomunikatu: '',
      // Komunikat wyskakującego okienka z potwierdzeniem akcji
      tekstPotwierdzeniaAkcji: '',
      // Wywoływanie funkcji po potwierdzeniu akcji
      nazwaWywolywanejFunkcji: '',
      parametrWywolywanejFunkcji: '',
      // ID wypłaty BTC, dla której podamy TXID
      idWyplatyBtcDoRealizacji: '',
      // Używane do blokowania przycisku wypłaty
      trwaRealizacjaWyplatyBtc: false,
      // TxID realizowanej wypłaty BTC
      txidRealizowanejWyplatyBtc: '',
      // Link do obsługi realizacji wypłaty BTC
      linkRealizacjiWyplatyBtc: '',
    }
  },
  computed: {
    komunikatToastowy() {
      return this.$refs.komunikatToastowy;
    },
    potwierdzenieAkcji() {
      return this.$refs.potwierdzenieAkcji;
    },
    formularzRealizacjiWyplatyBtc() {
      return this.$refs.formularzRealizacjiWyplatyBtc;
    },
  },
  created() {
  },
  mounted() {
    this.wczytajTooltipy();
  },
  methods: {
    wczytajTooltipy() {
      var listaTriggerowTooltipa = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
      var listaTooltipow = listaTriggerowTooltipa.map(function (tooltip) {
        return new bootstrap.Tooltip(tooltip)
      })
    },
    // Kopiuje przekazany tekst do schowka
    skopiujDoSchowka(tekst) {
      navigator.clipboard.writeText(tekst);
      this.pokazKomunikat('Skopiowano do schowka');
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
    // Wywołuje pokazywanie okna potwierdzenia akcji
    potwierdzenieAnulowaniaWyplatyBtc(wyplataBtc) {
      this.pokazPotwierdzenieAkcji('Czy na pewno chcesz anulować wypłatę?', 'anulujWyplateBtc', wyplataBtc);
    },
    // Wywołuje pokazywanie okna potwierdzenia akcji
    potwierdzenieZablokowaniaWyplatyBtc(wyplataBtc) {
      this.pokazPotwierdzenieAkcji('Czy na pewno chcesz zablokować wypłatę?', 'zablokujWyplateBtc', wyplataBtc);
    },
    // Pokazuje okno realizacji wypłaty BTC
    realizacjaWyplatyBtc(wyplataBtc) {
      let formularzRealizacjiWyplatyBtc = new bootstrap.Modal(this.formularzRealizacjiWyplatyBtc)
      this.idWyplatyBtcDoRealizacji = wyplataBtc;
      this.linkRealizacjiWyplatyBtc = "/admin/wyplatyBtc/zrealizujWyplate/" + wyplataBtc;
      formularzRealizacjiWyplatyBtc.show();
    },

    // Anuluje wypłatę BTC
    anulujWyplateBtc(wyplataBtc) {
      this.$refs.formularzWyplatyBtc.action = "/admin/wyplatyBtc/anulujWyplate/" + wyplataBtc;
      this.$refs.formularzWyplatyBtc.submit();
    },
    // Blokuje wypłatę BTC
    zablokujWyplateBtc(wyplataBtc) {
      this.$refs.formularzWyplatyBtc.action = "/admin/wyplatyBtc/zablokujWyplate/" + wyplataBtc;
      this.$refs.formularzWyplatyBtc.submit();
    },
    // Waliduj oraz wyślij formularz
    walidujOrazWyslijFormularz(zdarzenie) {

      if (this.walidujFormularz()) {
        this.trwaRealizacjaWyplatyBtc = true;
      } else {
        zdarzenie.preventDefault();
      }
    },
    // Waliduj formularz
    walidujFormularz() {

      if (!this.walidujTxid()) {
        return false;
      }
      return true;

    },

    // Waliduj TxID
    walidujTxid() {

      let dopuszczalnyWzor = /^[0-9a-zA-Z]+$/;
      if (!this.txidRealizowanejWyplatyBtc.match(dopuszczalnyWzor)) {
        this.pokazKomunikat("Nieprawidłowy format TxID");
        return false;
      }
      return true;

    },
  }
}

Vue.createApp(wyplatyBtc).mount('#wyplatyBtc');