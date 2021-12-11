 @extends('layouts.app', ['aktywneMenu' => "index"])
 @push('js')
     <script>
         const prowizjaUzytkownikaProcent = {!! json_encode(config('app.prowizjaTransakcyjnaProcent'), JSON_HEX_TAG) !!};
         const minimalnaKwotaBtcWOfercie = {!! json_encode(config('app.minimalnaKwotaBtcWOfercie'), JSON_HEX_TAG) !!};
         const minimalnyKursBtcWOfercie = {!! json_encode(config('app.minimalnyKursBtcWOfercie'), JSON_HEX_TAG) !!};
         const minimalnaWartoscPlnWOfercie = {!! json_encode(config('app.minimalnaWartoscPlnWOfercie'), JSON_HEX_TAG) !!};
     </script>
     <script src="{{ asset('js/libs/PomocnikLiczbowy.js') }}"></script>
     <script src="{{ asset('js/index.js') }}"></script>
 @endpush
 @section('content')
     <div id="index">
         @include('layouts.modals.potwierdzenieAkcji')
         @include('layouts.toasts.komunikat')
         <div class="container my-3">
             {{-- Pasek cen --}}
             <div class="row gy-1" id="pasek_cen">
                 <a id="BTC_PLN"
                     class="py-0 px-2 nav-link col-12 col-sm-6 col-lg-auto ms-auto ms-xxl-0 text-end text-sm-start order-first border-lg-end">
                     BTC/PLN: <span v-cloak>@{{ aktualnyKursSformatowany }}</span>
                 </a>
                 <a id="wolumen"
                     class="py-0 px-2 nav-link col-12 col-sm-6 col-lg-auto text-end text-sm-end order-1 border-lg-end">
                     Wolumen: <span v-cloak>@{{ wolumen24hSformatowany }}</span>
                 </a>
                 <div class="w-100 d-none d-lg-block d-xxl-none order-lg-4"></div>
                 <a id="najnizszy_kurs"
                     class="py-0 px-2 nav-link col-12 col-sm-6 col-lg-auto text-end text-sm-start order-2 border-lg-end">
                     Najniższy z 24h: <span v-cloak>@{{ najnizszyKurs24hSformatowany }}</span>
                 </a>
                 <a id="najwyzszy_kurs"
                     class="py-0 px-2 ps-lg-2 pe-lg-3  nav-link col-12 col-sm-6 col-lg-auto text-end text-sm-start order-4 order-lg-3">
                     Najwyższy z 24h: <span v-cloak>@{{ najwyzszyKurs24hSformatowany }}</span>
                 </a>
                 <a id="saldo_PLN"
                     class="py-0 px-2 nav-link col-12 col-sm-6 col-lg-auto ms-auto text-end text-sm-end order-3 order-lg-4 border-lg-end">
                     Saldo PLN: @auth <span v-cloak>@{{ saldoPlnUzytkownikaSformatowane }}</span> @endauth @guest -
                 @endguest
             </a>
             <a id="saldo_BTC"
                 class="py-0 px-2 ps-lg-2 pe-lg-3 nav-link col-12 col-sm-6 col-lg-auto text-end order-last">
                 Saldo BTC: @auth <span v-cloak>@{{ saldoBtcUzytkownikaSformatowane }}</span> @endauth @guest -
             @endguest
         </a>
     </div>
 </div>
 <div class="container-fluid border-bottom">
 </div>
 <div class="container mt-3 mt-md-3 mt-xl-4">
     <div class="row" id="komunikat">
         <div class="col-12">
             @include('layouts.alert')
         </div>
     </div>
     <div class="row mt-2 mt-md-2 mt-xl-3 gx-4 gy-2">
         {{-- Bloki kupna i sprzedaży --}}
         <div class="col-12 col-lg-6 mb-3">
             <div class="border rounded-top border-start border-end border-bottom rounded-bottom h-100">
                 <form class="mb-0">
                     @csrf
                     <input type="hidden" name="typ" value="zakup">
                     <div class="row g-0">
                         <div class="col-12">
                             <div class="niebieskie-tlo py-3 rounded-top text-center text-light">Kup BTC<span v-on:click="testAxios()"> test </span></div>
                         </div>
                     </div>
                     <div class="row pt-3 pb-4 px-2 px-sm-4 g-0">
                         <div class="col-12">
                             <div class="row text-center">
                                 <div class="col-4">
                                     <label for="kursZakupu" class="mb-2">Kurs PLN</label>
                                     <input type="text" class="form-control lekko-niebieskie-tlo" name="kursZakupu"
                                         required v-model="kursZakupu" v-on:input="wyliczKwoteZakupuPln">
                                 </div>
                                 <div class="col-4">
                                     <label for="kwotaZakupuBtc" class="mb-2">Kwota BTC</label>
                                     <input type="text" class="form-control lekko-niebieskie-tlo"
                                         name="kwotaZakupuBtc" required v-model="kwotaZakupuBtc"
                                         v-on:input="wyliczKwoteZakupuPln">
                                 </div>
                                 <div class="col-4">
                                     <label for="kwotaZakupuPln" class="mb-2">Wartość PLN</label>
                                     <input type="text" class="form-control lekko-niebieskie-tlo"
                                         name="kwotaZakupuPln" disabled v-bind:value="kwotaZakupuPlnSformatowana">
                                 </div>
                             </div>
                             <div class="row pt-3">
                                 <div class="col-auto">
                                     <p class="m-0 kursorLapki" v-on:click="kupLubSprzedajWszystko('zakup')">Kup
                                         wszystko</p>
                                 </div>
                             </div>
                             <div class="row pt-3">
                                 <div class="col-12">
                                     <p class="m-0">Otrzymasz: <span
                                             v-cloak>@{{ otrzymaszBtcSformatowane }}</span> BTC
                                     </p>
                                 </div>
                             </div>
                             <div class="row pt-3">
                                 <div class="col-12">
                                     <button type="button" class="btn btn-success w-100" @can('wystawOferte',
                                             App\Models\Oferta::class) v-bind:disabled='trwaWystawianieOferty'
                                             v-on:click="wystawOferte('zakup')" @else disabled @endcan>@auth
                                             @if (!$uzytkownik->zweryfikowany) Nie
                                                 jesteś
                                                 zweryfikowany
                                         @elseif($uzytkownik->zablokowany) Twoje konto jest zablokowane @else
                                             Zatwierdź @endif @endauth @guest Nie jesteś
                                         zalogowany
                                     @endguest
                                 </button>
                             </div>
                         </div>
                     </div>
                 </div>
             </form>
         </div>
     </div>

     <div class="col-12 col-lg-6 mb-3">
         <div class="border rounded-top border-start border-end border-bottom rounded-bottom h-100">
             <form class="mb-0">
                 @csrf
                 <input type="hidden" name="typ" value="sprzedaż">
                 <div class="row g-0">
                     <div class="col-12">
                         <div class="niebieskie-tlo py-3 rounded-top text-center text-light">Sprzedaj BTC
                         </div>
                     </div>
                 </div>
                 <div class="row pt-3 pb-4 px-2 px-sm-4 g-0">
                     <div class="col-12">
                         <div class="row text-center">
                             <div class="col-4">
                                 <label for="kursSprzedazy" class="mb-2">Kurs PLN</label>
                                 <input type="text" class="form-control lekko-niebieskie-tlo"
                                     name="kursSprzedazy" required v-model="kursSprzedazy"
                                     v-on:input="wyliczKwoteSprzedazyPln">
                             </div>
                             <div class="col-4">
                                 <label for="kwotaSprzedazyBtc" class="mb-2">Kwota BTC</label>
                                 <input type="text" class="form-control lekko-niebieskie-tlo"
                                     name="kwotaSprzedazyBtc" required v-model="kwotaSprzedazyBtc"
                                     v-on:input="wyliczKwoteSprzedazyPln">
                             </div>
                             <div class="col-4">
                                 <label for="kwotaSprzedazyPln" class="mb-2">Wartość PLN</label>
                                 <input type="text" class="form-control lekko-niebieskie-tlo"
                                     name="kwotaSprzedazyPln" disabled
                                     v-bind:value="kwotaSprzedazyPlnSformatowana">
                             </div>
                         </div>
                         <div class="row pt-3">
                             <div class="col-auto">
                                 <p class="m-0 kursorLapki" v-on:click="kupLubSprzedajWszystko('sprzedaż')">
                                     Sprzedaj
                                     wszystko</p>
                             </div>
                         </div>
                         <div class="row pt-3">
                             <div class="col-12">
                                 <p class="m-0">Otrzymasz: <span
                                         v-cloak>@{{ otrzymaszPlnSformatowane }}</span> PLN
                                 </p>
                             </div>
                         </div>
                         <div class="row pt-3">
                             <div class="col-12">
                                 <button type="button" class="btn btn-success w-100" @can('wystawOferte',
                                         App\Models\Oferta::class) v-bind:disabled='trwaWystawianieOferty'
                                         v-on:click="wystawOferte('sprzedaż')" @else disabled @endcan>@auth
                                         @if (!$uzytkownik->zweryfikowany) Nie
                                             jesteś
                                             zweryfikowany
                                     @elseif($uzytkownik->zablokowany) Twoje konto jest zablokowane @else
                                         Zatwierdź @endif @endauth @guest Nie jesteś
                                     zalogowany
                                 @endguest
                             </button>
                         </div>
                     </div>
                 </div>
             </div>
         </form>
     </div>
 </div>

 {{-- Bloki ofert kupna i sprzedaży --}}
 <div class="col-12 col-lg-6 mb-3">
     <div class="border rounded-top border-start border-end border-bottom rounded-bottom h-100">
         <form class="mb-0">
             <div class="row g-0">
                 <div class="col-12">
                     <div class="niebieskie-tlo p-3 rounded-top text-center text-light">Oferty kupna
                     </div>
                 </div>
             </div>
             <div class="stala-wysokosc-tabeli">
                 <table class="table align-middle mb-0">
                     <thead class="bezowe-tlo przyklejony-naglowek-tabeli">
                         <tr>
                             <th scope="col">Kurs PLN</th>
                             <th scope="col">Kwota BTC</th>
                             <th scope="col">Wartość PLN</th>
                         </tr>
                     </thead>
                     <tbody>
                         <tr class="kursorLapki" v-cloak v-for="(ofertaZakupu, kurs) in ofertyZakupu"
                             :key="kurs" v-on:click="skopiujKurs('zakup', kurs)">
                             <td>@{{ kurs }}</td>
                             <td>@{{ ofertaZakupu . pozostala_kwota_btc }}</td>
                             <td>@{{ ofertaZakupu . pozostala_kwota_pln }}</td>
                         </tr>
                     </tbody>
                 </table>
             </div>
         </form>
     </div>
 </div>
 <div class="col-12 col-lg-6 mb-3">
     <div class="border rounded-top border-start border-end border-bottom rounded-bottom h-100">
         <form class="mb-0">
             <div class="row g-0">
                 <div class="col-12">
                     <div class="niebieskie-tlo p-3 rounded-top text-center text-light">Oferty sprzedaży
                     </div>
                 </div>
             </div>
             <div class="stala-wysokosc-tabeli">
                 <table class="table align-middle mb-0">
                     <thead class="bezowe-tlo przyklejony-naglowek-tabeli">
                         <tr>
                             <th scope="col">Kurs PLN</th>
                             <th scope="col">Kwota BTC</th>
                             <th scope="col">Wartość PLN</th>
                         </tr>
                     </thead>
                     <tbody>
                         <tr class="kursorLapki" v-cloak
                             v-for="(ofertaSprzedazy, kurs) in ofertySprzedazy" :key="kurs"
                             v-on:click="skopiujKurs('sprzedaż', kurs)">
                             <td>@{{ kurs }}</td>
                             <td>@{{ ofertaSprzedazy . pozostala_kwota_btc }}</td>
                             <td>@{{ ofertaSprzedazy . pozostala_kwota_pln }}</td>
                         </tr>
                     </tbody>
                 </table>
             </div>
         </form>
     </div>
 </div>
 {{-- Blok ostatnich transakcji --}}

 <div class="col-12 col-lg-6 mb-3">
     <div class="border rounded-top border-start border-end border-bottom rounded-bottom h-100">
         <div class="row g-0">
             <div class="col-12">
                 <div class="niebieskie-tlo p-3 rounded-top text-center text-light">Ostatnie transakcje
                 </div>
             </div>
         </div>
         <div class="stala-wysokosc-tabeli">
             <table class="table align-middle mb-0">
                 <thead class="bezowe-tlo przyklejony-naglowek-tabeli">
                     <tr>
                         <th scope="col">Kurs PLN</th>
                         <th scope="col">Kwota BTC</th>
                         <th scope="col">Wartość PLN</th>
                         <th scope="col">Data</th>
                     </tr>
                 </thead>
                 <tbody>
                     <tr v-cloak v-for="transakcja in ostatnieTransakcje" :key="transakcja.id">
                         <td>@{{ transakcja . kurs }}</td>
                         <td>@{{ transakcja . kwota_btc }}</td>
                         <td>@{{ transakcja . kwota_pln }}</td>
                         <td>@{{ transakcja . created_at }}</td>
                     </tr>
                 </tbody>
             </table>
         </div>
     </div>
 </div>

 {{-- Blok oferty użytkownika --}}

 <div class="col-12 col-lg-6 mb-3">
     <div class="border rounded-top border-start border-end border-bottom rounded-bottom h-100">
         <form class="mb-0">
             <div class="row g-0">
                 <div class="col-12">
                     <div class="niebieskie-tlo p-3 rounded-top text-center text-light">Moje oferty</div>
                 </div>
             </div>
             <div class="stala-wysokosc-tabeli">
                 <table class="table align-middle mb-0">
                     <thead class="bezowe-tlo przyklejony-naglowek-tabeli">
                         <tr>
                             <th scope="col">Kurs PLN</th>
                             <th scope="col">Kwota BTC</th>
                             <th scope="col">Wartość PLN</th>
                             <th scope="col">Typ</th>
                             <th scope="col">{{-- Kolumna dla akcji usunięcia --}}</th>
                         </tr>
                     </thead>
                     <tbody>
                         @auth
                             <tr v-cloak v-for="oferta in ofertyUzytkownika" :key="oferta.id">
                                 <td>@{{ oferta . kurs }}</td>
                                 <td>@{{ oferta . pozostala_kwota_btc }}</td>
                                 <td>@{{ oferta . pozostala_kwota_pln }}</td>
                                 <td>
                                     <span v-if="oferta.typ === 'Zakup'">Zakup</span>
                                     <span v-else-if="oferta.typ === 'Sprzedaż'">Sprzedaż</span>
                                 </td>
                                 <td><button type="button" class="btn btn-link p-0"
                                         v-on:click="potwierdzenieAnulowaniaOferty(oferta.id)"><i
                                             class="fas fa-times"></i></button></td>
                             </tr>
                         @endauth
                     </tbody>
                 </table>
             </div>
         </form>
     </div>
 </div>

</div>
</div>
</div>
@endsection
