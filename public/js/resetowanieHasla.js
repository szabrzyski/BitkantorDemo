const FormularzResetowaniaHasla = {
  data() {
    return {
      email: email
    }
  },
  methods: {
    walidujOrazWyslijFormularz(e) {

      let emailZwalidowany = false;

      // walidacja emaila
      if (this.email.length > 0 && this.email.length <= 255 && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.email)) {
        emailZwalidowany = true;
      }

      if (emailZwalidowany) {
        return true;
      } else {
        alert('W formularzu znajdują się błędy');
      }

      e.preventDefault();
    }
  }
}

Vue.createApp(FormularzResetowaniaHasla).mount('#formularzResetowaniaHasla')
