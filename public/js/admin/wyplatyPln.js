const wyplatyPln = {
  data() {
    return {
      // Komunikat toastowy
      tekstKomunikatu: '',
      // Komunikat wyskakującego okienka z potwierdzeniem akcji
      tekstPotwierdzeniaAkcji: '',
      // Wywoływanie funkcji po potwierdzeniu akcji
      nazwaWywolywanejFunkcji: '',
      parametrWywolywanejFunkcji: ''
    }
  },
  computed: {
    komunikatToastowy() {
      return this.$refs.komunikatToastowy;
    },
    potwierdzenieAkcji() {
      return this.$refs.potwierdzenieAkcji;
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
    potwierdzenieAnulowaniaWyplatyPln(wyplataPln) {
      this.pokazPotwierdzenieAkcji('Czy na pewno chcesz anulować wypłatę?', 'anulujWyplatePln', wyplataPln);
    },
    // Wywołuje pokazywanie okna potwierdzenia akcji
    potwierdzenieZablokowaniaWyplatyPln(wyplataPln) {
      this.pokazPotwierdzenieAkcji('Czy na pewno chcesz zablokować wypłatę?', 'zablokujWyplatePln', wyplataPln);
    },
    // Wywołuje pokazywanie okna potwierdzenia akcji
    potwierdzenieRealizacjiWyplatyPln(wyplataPln) {
      this.pokazPotwierdzenieAkcji('Czy wypłata została zrealizowana?', 'zrealizujWyplatePln', wyplataPln);
    },
    // Anuluje wypłatę PLN
    anulujWyplatePln(wyplataPln) {
      this.$refs.formularzWyplatyPln.action = "/admin/wyplatyPln/anulujWyplate/" + wyplataPln;
      this.$refs.formularzWyplatyPln.submit();
    },
    // Blokuje wypłatę PLN
    zablokujWyplatePln(wyplataPln) {
      this.$refs.formularzWyplatyPln.action = "/admin/wyplatyPln/zablokujWyplate/" + wyplataPln;
      this.$refs.formularzWyplatyPln.submit();
    },
    // Realizuje wypłatę PLN
    zrealizujWyplatePln(wyplataPln) {
      this.$refs.formularzWyplatyPln.action = "/admin/wyplatyPln/zrealizujWyplate/" + wyplataPln;
      this.$refs.formularzWyplatyPln.submit();
    }
  }
}

Vue.createApp(wyplatyPln).mount('#wyplatyPln');