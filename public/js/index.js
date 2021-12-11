const index = {
  data() {
    return {
      // Mininalne kwoty w ofercie
      minimalnaKwotaBtcWOfercie: minimalnaKwotaBtcWOfercie,
      minimalnyKursBtcWOfercie: minimalnyKursBtcWOfercie,
      minimalnaWartoscPlnWOfercie: minimalnaWartoscPlnWOfercie,
      // Statystyki
      aktualnyKurs: 0,
      najnizszyKurs24h: 0,
      najwyzszyKurs24h: 0,
      wolumen24h: 0,
      // Saldo oraz prowizja użytkownika
      saldoPlnUzytkownika: 0,
      saldoBtcUzytkownika: 0,
      prowizjaUzytkownikaProcent: prowizjaUzytkownikaProcent,
      // Blok zakupu BTC
      kursZakupu: '',
      kwotaZakupuBtc: '',
      kwotaZakupuPln: 0,
      otrzymaszBtc: 0,
      // Blok sprzedaży BTC
      kursSprzedazy: '',
      kwotaSprzedazyBtc: '',
      kwotaSprzedazyPln: 0,
      otrzymaszPln: 0,
      // Blok ostatnich transakcji
      ostatnieTransakcje: [],
      // Blok ofert użytkownika
      ofertyUzytkownika: [],
      // Blok ofert zakupu BTC
      ofertyZakupu: [],
      // Blok ofert sprzedaży BTC
      ofertySprzedazy: [],
      // Komunikat toastowy
      tekstKomunikatu: '',
      // Komunikat wyskakującego okienka z potwierdzeniem akcji
      tekstPotwierdzeniaAkcji: '',
      // Wywoływanie funkcji po potwierdzeniu akcji
      nazwaWywolywanejFunkcji: '',
      parametrWywolywanejFunkcji: '',
      // Używane do blokowania przycisków wystawiania oferty
      trwaWystawianieOferty: false,
      // Instancje metod wywoływanych okresowo, są resetowane przy każdym nowym wywołaniu metody
      instancjaMetodyPobieraniaOstatnichTransakcji: null,
      instancjaMetodyPobieraniaOfert: null,
      instancjaMetodyPobieraniaStatystyk: null,
      instancjaMetodyPobieraniaSaldaOrazProwizji: null,
      // Pomocnik liczbowy
      pomocnikLiczbowy: new PomocnikLiczbowy()
    }
  },
  computed: {
    saldoPlnUzytkownikaSformatowane() {
      return this.saldoPlnUzytkownika.toFixed(2);
    },
    saldoBtcUzytkownikaSformatowane() {
      return this.saldoBtcUzytkownika.toFixed(8);
    },
    prowizjaUzytkownikaProcentSformatowana() {
      return this.prowizjaUzytkownikaProcent.toFixed(2);
    },
    aktualnyKursSformatowany() {
      return this.aktualnyKurs.toFixed(2);
    },
    najnizszyKurs24hSformatowany() {
      return this.najnizszyKurs24h.toFixed(2);
    },
    najwyzszyKurs24hSformatowany() {
      return this.najwyzszyKurs24h.toFixed(2);
    },
    wolumen24hSformatowany() {
      return this.wolumen24h.toFixed(8);
    },
    otrzymaszBtcSformatowane() {
      return Number(this.otrzymaszBtc).toFixed(8);
    },
    otrzymaszPlnSformatowane() {
      return Number(this.otrzymaszPln).toFixed(2);
    },
    kwotaZakupuPlnSformatowana() {
      return Number(this.kwotaZakupuPln).toFixed(2);
    },
    kwotaSprzedazyPlnSformatowana() {
      return Number(this.kwotaSprzedazyPln).toFixed(2);
    },
    komunikatToastowy() {
      return this.$refs.komunikatToastowy;
    },
    potwierdzenieAkcji() {
      return this.$refs.potwierdzenieAkcji;
    }
  },
  created() {
    this.pobierzStatystyki();
    this.pobierzSaldoOrazProwizje();
    this.pobierzOferty();
    this.pobierzOstatnieTransakcje();
  },
  methods: {
    walidujFormularz(typOferty) {

      // Walidacja typu oferty i przypisanie kwot do zmiennych
      if (typOferty == 'zakup') {
        var kwotaBtc = this.kwotaZakupuBtc;
        var kurs = this.kursZakupu;
      } else if (typOferty == 'sprzedaż') {
        var kwotaBtc = this.kwotaSprzedazyBtc;
        var kurs = this.kursSprzedazy;
      } else {
        return false;
      }

      // Walidacja kwoty BTC
      if (isNaN(kwotaBtc) || kwotaBtc < this.minimalnaKwotaBtcWOfercie) {
        this.pokazKomunikat('Minimalna kwota BTC to: ' + this.minimalnaKwotaBtcWOfercie);
        return false;
      }

      // Walidacja kursu
      if (isNaN(kurs) || kurs < this.minimalnyKursBtcWOfercie) {
        this.pokazKomunikat('Minimalny kurs BTC/PLN to: ' + this.minimalnyKursBtcWOfercie);
        return false;
      }

      // Walidacja wartości PLN
      if ((kwotaBtc * kurs) < this.minimalnaWartoscPlnWOfercie) {
        this.pokazKomunikat('Minimalna wartość oferty to: ' + this.minimalnaWartoscPlnWOfercie + " PLN");
        return false;
      }

      return true;

    },
    // Wyliczanie wartości PLN i formatowanie pól w formularzu zakupu BTC
    wyliczKwoteZakupuPln() {

      let kursZakupuSformatowany = this.kursZakupu.replace(',', '.').replace(/[^0-9*\.?0-9*]/, '').split('.').slice(0, 2).join('.');
      this.kursZakupu = (kursZakupuSformatowany.indexOf(".") >= 0) ? (kursZakupuSformatowany.substr(0, kursZakupuSformatowany.indexOf(".")) + kursZakupuSformatowany.substr(kursZakupuSformatowany.indexOf("."), 3)) : kursZakupuSformatowany;

      let kwotaZakupuBtcSformatowana = this.kwotaZakupuBtc.replace(',', '.').replace(/[^0-9*\.?0-9*]/, '').split('.').slice(0, 2).join('.');
      this.kwotaZakupuBtc = (kwotaZakupuBtcSformatowana.indexOf(".") >= 0) ? (kwotaZakupuBtcSformatowana.substr(0, kwotaZakupuBtcSformatowana.indexOf(".")) + kwotaZakupuBtcSformatowana.substr(kwotaZakupuBtcSformatowana.indexOf("."), 9)) : kwotaZakupuBtcSformatowana;

      let kwotaZakupuPlnWyliczona = this.pomocnikLiczbowy.zaokraglWGorePoPrzecinku(this.kursZakupu * this.kwotaZakupuBtc, 2);
      this.kwotaZakupuPln = (isNaN(kwotaZakupuPlnWyliczona)) ? 0.00 : Number(kwotaZakupuPlnWyliczona);

      let otrzymaszBtcWyliczone = this.pomocnikLiczbowy.zaokraglWDolPoPrzecinku(this.kwotaZakupuBtc - (this.prowizjaUzytkownikaProcent * this.kwotaZakupuBtc / 100), 8);
      this.otrzymaszBtc = (isNaN(otrzymaszBtcWyliczone)) ? 0.00000000 : Number(otrzymaszBtcWyliczone);
    },
    // Wyliczanie wartości PLN i formatowanie pól w formularzu sprzedaży BTC
    wyliczKwoteSprzedazyPln() {

      let kursSprzedazySformatowany = this.kursSprzedazy.replace(',', '.').replace(/[^0-9*\.?0-9*]/, '').split('.').slice(0, 2).join('.');
      this.kursSprzedazy = (kursSprzedazySformatowany.indexOf(".") >= 0) ? (kursSprzedazySformatowany.substr(0, kursSprzedazySformatowany.indexOf(".")) + kursSprzedazySformatowany.substr(kursSprzedazySformatowany.indexOf("."), 3)) : kursSprzedazySformatowany;

      let kwotaSprzedazyBtcSformatowana = this.kwotaSprzedazyBtc.replace(',', '.').replace(/[^0-9*\.?0-9*]/, '').split('.').slice(0, 2).join('.');
      this.kwotaSprzedazyBtc = (kwotaSprzedazyBtcSformatowana.indexOf(".") >= 0) ? (kwotaSprzedazyBtcSformatowana.substr(0, kwotaSprzedazyBtcSformatowana.indexOf(".")) + kwotaSprzedazyBtcSformatowana.substr(kwotaSprzedazyBtcSformatowana.indexOf("."), 9)) : kwotaSprzedazyBtcSformatowana;

      let kwotaSprzedazyPlnWyliczona = this.pomocnikLiczbowy.zaokraglWDolPoPrzecinku(this.kursSprzedazy * this.kwotaSprzedazyBtc, 2);
      this.kwotaSprzedazyPln = (isNaN(kwotaSprzedazyPlnWyliczona)) ? 0.00 : Number(kwotaSprzedazyPlnWyliczona);

      let otrzymaszPlnWyliczone = this.pomocnikLiczbowy.zaokraglWDolPoPrzecinku(this.kwotaSprzedazyPln - (this.prowizjaUzytkownikaProcent * this.kwotaSprzedazyPln / 100), 2);
      this.otrzymaszPln = (isNaN(otrzymaszPlnWyliczone)) ? 0.00 : Number(otrzymaszPlnWyliczone);
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
    // Funkcja zatrzymuje instancje okresowo wywoływanej metody
    zatrzymajPoprzedniaInstancjeMetody(instancjaMetody) {
      if (instancjaMetody) {
        clearTimeout(instancjaMetody);
      }
      return true;
    },
    // Pokazuje okno potwierdzenia akcji
    pokazPotwierdzenieAkcji(tekstPotwierdzeniaAkcji, nazwaWywolywanejFunkcji, parametrWywolywanejFunkcji = '') {
      this.nazwaWywolywanejFunkcji = nazwaWywolywanejFunkcji;
      this.tekstPotwierdzeniaAkcji = tekstPotwierdzeniaAkcji;
      this.parametrWywolywanejFunkcji = parametrWywolywanejFunkcji;
      let potwierdzenieAkcji = new bootstrap.Modal(this.potwierdzenieAkcji)
      potwierdzenieAkcji.show();
    },
    potwierdzenieAnulowaniaOferty(idOferty) {
      this.pokazPotwierdzenieAkcji('Czy na pewno chcesz anulować ofertę?', 'anulujOferte', idOferty);
    },
    // Pobiera 100 ostatnich transakcji
    async pobierzOstatnieTransakcje() {
      this.zatrzymajPoprzedniaInstancjeMetody(this.instancjaMetodyPobieraniaOstatnichTransakcji);
      let instancjaVue = this;
      let odpowiedzAxios = await axios({
        method: 'GET',
        url: '/pobierzOstatnieTransakcje',
        timeout: 300000
      })
        .then((response) => {
          this.ostatnieTransakcje = response.data.transakcje;
          TRYB_DEBUG && console.log('Pobrano ostatnie transakcje');
        })
        .catch(function (error) {
          instancjaVue.obsluzStandardowyBlad(error);
        })
        .then(function () {
          instancjaVue.instancjaMetodyPobieraniaOstatnichTransakcji = setTimeout(() => { instancjaVue.pobierzOstatnieTransakcje() }, 10000);
        });

    },
    // Pobiera wszystkie aktywne oferty
    async pobierzOferty() {
      this.zatrzymajPoprzedniaInstancjeMetody(this.instancjaMetodyPobieraniaOfert);
      let instancjaVue = this;
      let odpowiedzAxios = await axios({
        method: 'GET',
        url: '/pobierzOferty',
        timeout: 300000
      })
        .then((response) => {
          this.ofertyUzytkownika = response.data.ofertyUzytkownika;
          this.ofertyZakupu = response.data.ofertyZakupu;
          this.ofertySprzedazy = response.data.ofertySprzedazy;
          TRYB_DEBUG && console.log('Pobrano oferty');
        })
        .catch(function (error) {
          instancjaVue.obsluzStandardowyBlad(error);
        })
        .then(function () {
          instancjaVue.instancjaMetodyPobieraniaOfert = setTimeout(() => { instancjaVue.pobierzOferty() }, 10000);
        });
    },
    // Pobiera aktualny kurs, wolumen z 24h, najniższy oraz najwyższy kurs z 24h
    async pobierzStatystyki() {
      this.zatrzymajPoprzedniaInstancjeMetody(this.instancjaMetodyPobieraniaStatystyk);
      let instancjaVue = this;
      let odpowiedzAxios = await axios({
        method: 'GET',
        url: '/pobierzStatystyki',
        timeout: 300000
      })
        .then((response) => {
          this.aktualnyKurs = response.data.aktualnyKurs == null ? 0 : response.data.aktualnyKurs;
          this.najnizszyKurs24h = response.data.najnizszyKurs24h == null ? 0 : response.data.najnizszyKurs24h;
          this.najwyzszyKurs24h = response.data.najwyzszyKurs24h == null ? 0 : response.data.najwyzszyKurs24h;
          this.wolumen24h = response.data.wolumen24h == null ? 0 : response.data.wolumen24h;
          TRYB_DEBUG && console.log('Pobrano statystyki');
        })
        .catch(function (error) {
          instancjaVue.obsluzStandardowyBlad(error);
        })
        .then(function () {
          instancjaVue.instancjaMetodyPobieraniaStatystyk = setTimeout(() => { instancjaVue.pobierzStatystyki() }, 60000);
        });
    },
    // Pobiera saldo użytkownika oraz jego prowizję
    async pobierzSaldoOrazProwizje() {
      this.zatrzymajPoprzedniaInstancjeMetody(this.instancjaMetodyPobieraniaSaldaOrazProwizji);
      let instancjaVue = this;
      let odpowiedzAxios = await axios({
        method: 'GET',
        url: '/pobierzSaldoOrazProwizje',
        timeout: 300000
      })
        .then((response) => {
          if (response.status === 200) {
            this.saldoPlnUzytkownika = response.data.saldoPlnUzytkownika == null ? 0 : response.data.saldoPlnUzytkownika;
            this.saldoBtcUzytkownika = response.data.saldoBtcUzytkownika == null ? 0 : response.data.saldoBtcUzytkownika;
            this.prowizjaUzytkownikaProcent = response.data.prowizjaUzytkownikaProcent == null ? 0 : response.data.prowizjaUzytkownikaProcent;
            TRYB_DEBUG && console.log('Pobrano saldo i prowizję użytkownika');
          }
        })
        .catch(function (error) {
          instancjaVue.obsluzStandardowyBlad(error);
        })
        .then(function () {
          instancjaVue.instancjaMetodyPobieraniaSaldaOrazProwizji = setTimeout(() => { instancjaVue.pobierzSaldoOrazProwizje() }, 10000);
        });
    },
    // Wystawia ofertę zakupu lub sprzedaży
    async wystawOferte(typOferty) {

      if (!this.walidujFormularz(typOferty)) {
        return false;
      }

      if (typOferty == 'zakup') {
        var kwotaBtc = Number(this.kwotaZakupuBtc);
        var kurs = Number(this.kursZakupu);
      } else if (typOferty == 'sprzedaż') {
        var kwotaBtc = Number(this.kwotaSprzedazyBtc);
        var kurs = Number(this.kursSprzedazy);
      }

      let instancjaVue = this;
      this.trwaWystawianieOferty = true;
      let odpowiedzAxios = await axios({
        method: 'POST',
        url: '/oferty/wystaw',
        data: {
          typ: typOferty,
          kwotaBtc: kwotaBtc,
          kurs: kurs
        },
        timeout: 300000
      })
        .then((response) => {
          this.pokazKomunikat('Oferta została wystawiona');
          this.saldoPlnUzytkownika = Number(response.data.aktualneSaldoPlnWystawiajacego);
          this.saldoBtcUzytkownika = Number(response.data.aktualneSaldoBtcWystawiajacego);
          let wystawionaOferta = response.data.wystawionaOferta;
          if (wystawionaOferta.pozostala_kwota_btc > 0) {
            this.ofertyUzytkownika.push(wystawionaOferta);
          }

          if (wystawionaOferta.pozostala_kwota_btc !== wystawionaOferta.kwota_btc) {
            this.pobierzOstatnieTransakcje();
          }
          this.pobierzOferty();
          TRYB_DEBUG && console.log('Wystawiono ofertę');
        })
        .catch(function (error) {
          instancjaVue.obsluzStandardowyBlad(error);
        })
        .then(function () {
          instancjaVue.trwaWystawianieOferty = false;
        });

    },
    // Anuluje ofertę
    async anulujOferte(oferta) {
      let instancjaVue = this;
      let odpowiedzAxios = await axios({
        method: 'POST',
        url: '/oferty/anuluj/' + oferta,
        timeout: 300000
      })
        .then((response) => {
          let indexOferty = this.ofertyUzytkownika.findIndex(aktualnieSprawdzanaOferta => aktualnieSprawdzanaOferta.id === oferta);
          this.ofertyUzytkownika.splice(indexOferty, 1);
          this.saldoPlnUzytkownika = Number(response.data.aktualneSaldoPlnWystawiajacego);
          this.saldoBtcUzytkownika = Number(response.data.aktualneSaldoBtcWystawiajacego);
          this.pokazKomunikat('Oferta została anulowana');
          this.pobierzOferty();
          TRYB_DEBUG && console.log('Anulowano ofertę');
        })
        .catch(function (error) {
          instancjaVue.obsluzStandardowyBlad(error);
        })
        .then(function () {
        });
    },
    // Funkcja testowa
    async testAxios() {
      return true;

      let instancjaVue = this;
      let odpowiedzAxios = await axios({
        method: 'GET',
        url: '/pobierzStatystyki',
        timeout: 300000
      })
        .then((response) => {
          TRYB_DEBUG && console.log('Sukces', response.status);
          TRYB_DEBUG && console.log(response.data);
        })
        .catch(function (error) {
          let czyObsluzonoStandardowyBlad = instancjaVue.obsluzStandardowyBlad(error);
        })
        .then(function () {
          // setTimeout(() => { instancjaVue.pobierzOstatnieTransakcje() }, 10000);
        });

    },
    // Skopiuj kurs oferty do bloku zakupu/sprzedaży
    skopiujKurs(typOferty, kursOferty) {
      if (typOferty === 'zakup') {
        this.kursSprzedazy = kursOferty;
        this.wyliczKwoteSprzedazyPln();
      } else if (typOferty === 'sprzedaż') {
        this.kursZakupu = kursOferty;
        this.wyliczKwoteZakupuPln();
      }
    },
    // Kup lub sprzedaj bitcoiny do dostępnego salda
    kupLubSprzedajWszystko(typOferty) {
      if (typOferty === 'zakup') {
        if (this.saldoPlnUzytkownika > 0) {
          if (this.kursZakupu > 0) {
            this.kwotaZakupuBtc = this.pomocnikLiczbowy.zaokraglWDolPoPrzecinku(this.saldoPlnUzytkownika / this.kursZakupu, 8);
            this.wyliczKwoteZakupuPln();
          } else {
            this.pokazKomunikat("Podaj kurs zakupu");
          }
        }
      } else if (typOferty === 'sprzedaż') {
        if (this.saldoBtcUzytkownika > 0) {
          this.kwotaSprzedazyBtc = String(this.saldoBtcUzytkownika.toFixed(8));
          this.wyliczKwoteSprzedazyPln();
        }
      }
    },
    // Obsługiwanie standardowych błędów HTTP oraz naszych własnych
    obsluzStandardowyBlad(error) {
      let tekstKomunikatu = '';
      if (error.response) {
        let kodBledu = error.response.status;
        switch (kodBledu) {
          case 403:
            tekstKomunikatu = 'Brak uprawnień do wykonania czynności';
            break;
          case 404:
            tekstKomunikatu = 'Nie odnaleziono strony';
            break;
          case 419:
            tekstKomunikatu = 'Sesja wygasła';
            break;
          case 429:
            tekstKomunikatu = 'Przekroczono limit zapytań';
            break;
          case 500:
            tekstKomunikatu = 'Błąd serwera';
            break;
          case 502:
            tekstKomunikatu = 'Błąd serwera';
            break;
          case 503:
            tekstKomunikatu = 'Serwis jest niedostępny';
            break;
          case 504:
            tekstKomunikatu = 'Przekroczono czas odpowiedzi serwera';
            break;
          case 520:
          case 521:
            tekstKomunikatu = error.response.data.komunikat;
            break;
          default:
            tekstKomunikatu = 'Wystąpił nieoczekiwany błąd';
            TRYB_DEBUG && console.log('Otrzymałem status inny niż 2xx', error.response.status);
            TRYB_DEBUG && console.log('Dane', error.response.data);
            TRYB_DEBUG && console.log('Nagłówki', error.response.headers)
            TRYB_DEBUG && console.log('Konfiguracja', error.config);
        }

      } else if (error.request) {
        tekstKomunikatu = 'Brak odpowiedzi serwera';
        TRYB_DEBUG && console.log('Nie otrzymałem odpowiedzi od serwera', error.request);
        TRYB_DEBUG && console.log('Konfiguracja', error.config);
      } else {
        tekstKomunikatu = 'Wystąpił nieoczekiwany błąd';
        TRYB_DEBUG && console.log('Nieoczekiwany błąd podczas przygotowywania żądania', error.message);
        TRYB_DEBUG && console.log('Konfiguracja', error.config);
      }

      this.pokazKomunikat(tekstKomunikatu);
    }
  }
}

Vue.createApp(index).mount('#index');