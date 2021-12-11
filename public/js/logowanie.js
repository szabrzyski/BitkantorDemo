const FormularzLogowania = {
  data() {
    return {
      email: email,
      haslo: haslo
    }
  },
  methods: {
    walidujOrazWyslijFormularz(e) {

      let emailZwalidowany = false;
      let hasloZwalidowane = false;

      // walidacja emaila
      if (this.email.length > 0 && this.email.length <= 255 && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.email)) {
        emailZwalidowany = true;
      }

      // walidacja hasła
      if (this.haslo.length >= 8 && this.haslo.length <= 255) {
        hasloZwalidowane = true;
      }

      if (emailZwalidowany && hasloZwalidowane) {
        return true;
      } else {
        alert('W formularzu znajdują się błędy');
      }

      e.preventDefault();
    }
  }
}

Vue.createApp(FormularzLogowania).mount('#formularzLogowania')
