const FormularzFinalizacjiResetowaniaHasla = {
  data() {
    return {
      haslo: haslo,
      powtorzoneHaslo: powtorzoneHaslo
    }
  },
  methods: {
    walidujOrazWyslijFormularz(e) {

      let hasloZwalidowane = false;
      let powtorzoneHasloZwalidowane = false;

      // walidacja hasła
      if (this.haslo.length >= 8 && this.haslo.length <= 255) {
        hasloZwalidowane = true;
      }

      // walidacja powtórzonego hasła
      if (this.powtorzoneHaslo.length >= 8 && this.powtorzoneHaslo.length <= 255 && this.powtorzoneHaslo == this.haslo) {
        powtorzoneHasloZwalidowane = true;
      }

      if (hasloZwalidowane && powtorzoneHasloZwalidowane) {
        return true;
      } else {
        alert('W formularzu znajdują się błędy');
      }

      e.preventDefault();
    }
  }
}

Vue.createApp(FormularzFinalizacjiResetowaniaHasla).mount('#formularzFinalizacjiResetowaniaHasla')
