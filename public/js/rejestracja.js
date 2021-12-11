const FormularzRejestracji = {
  data() {
    return {
      email: email,
      haslo: haslo,
      regulamin: regulamin
    }
  },
  methods: {
    walidujOrazWyslijFormularz(e) {

      let emailZwalidowany = false;
      let hasloZwalidowane = false;
      let regulaminZwalidowany = false;

      // walidacja emaila
      if (this.email.length > 0 && this.email.length <= 255 && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.email)) {
        emailZwalidowany = true;
      }

      // walidacja hasła
      if (this.haslo.length >= 8 && this.haslo.length <= 255) {
        hasloZwalidowane = true;
      }

      // walidacja regulaminu
      if (this.regulamin) {
        regulaminZwalidowany = true;
      }

      if (emailZwalidowany && hasloZwalidowane && regulaminZwalidowany) {
        return true;
      } else {
        alert('W formularzu znajdują się błędy');
      }

      e.preventDefault();
    },
    test() {
      alert('ok');
    }
  }
}

Vue.createApp(FormularzRejestracji).mount('#formularzRejestracji')
