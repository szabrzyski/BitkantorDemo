 @extends('layouts.app', ['aktywneMenu' => "mojeKonto"])
 @push('js')
     <script>
         const minimalnaKwotaWyplatyPln = {!! json_encode(config('app.minimalnaKwotaWyplatyPln'), JSON_HEX_TAG) !!};
         const prowizjaZaWyplatePln = {!! json_encode(config('app.prowizjaZaWyplatePln'), JSON_HEX_TAG) !!};
     </script>
     <script src="{{ asset('js/libs/PomocnikLiczbowy.js') }}"></script>
     <script src="{{ asset('js/saldoPln.js') }}"></script>
 @endpush
 @section('content')
     <div id="saldoPln">
         @include('layouts.modals.potwierdzenieAkcji')
         @include('layouts.toasts.komunikat')
         @include('layouts.dolnyPasekNawigacji', ['aktywneDolneMenu' => "saldoPln"])
         <div class="container-fluid border-bottom">
         </div>
         <div class="container mt-3 mt-md-3 mt-xl-4">
             <div class="row" id="komunikat">
                 <div class="col-12">
                     @include('layouts.alert')
                 </div>
             </div>
             <div class="row mt-2 mt-md-2 mt-xl-3 mb-4 gx-4 gy-2">

                 {{-- Bloki wpłaty PLN --}}

                 <div class="col-12 col-lg-6 mb-3">
                     <div class="border rounded-top border-start border-end border-bottom rounded-bottom h-100">
                         <div class="row g-0">
                             <div class="col-12">
                                 <div class="niebieskie-tlo py-3 rounded-top text-center text-light">Wpłata PLN</div>
                             </div>
                         </div>
                         <div class="row pb-4 pt-3 px-2 px-sm-4 g-0">
                             <div class="col-12">
                                 <div class="row mb-3 align-items-end">
                                     <div class="col-12 col-sm-9 pe-sm-2 mb-3 mb-sm-0">
                                         <label for="nazwaOdbiorcyPrzelewu" class="mb-2">Nazwa odbiorcy</label>
                                         <input type="text" class="form-control" id="nazwaOdbiorcyPrzelewu"
                                             name="nazwaOdbiorcyPrzelewu" ref="nazwaOdbiorcyPrzelewu" readonly
                                             value="{{ config('app.nazwaOdbiorcyPrzelewu') }}">
                                     </div>
                                     <div class="col-12 col-sm-3 ps-sm-2">
                                         <button type="button" class="btn btn-success w-100"
                                             v-on:click="skopiujDoSchowka('{{ config('app.nazwaOdbiorcyPrzelewu') }}')">Kopiuj</button>
                                     </div>
                                 </div>
                                 <div class="row mb-3 align-items-end">
                                     <div class="col-12 col-sm-9 pe-sm-2 mb-3 mb-sm-0">
                                         <label for="numerKontaDoWplaty" class="mb-2">Konto bankowe</label>
                                         <input type="text" class="form-control" id="numerKontaDoWplaty"
                                             name="numerKontaDoWplaty" ref="numerKontaDoWplaty" readonly
                                             value="{{ config('app.kontoBankowe') }}">
                                     </div>
                                     <div class="col-12 col-sm-3 ps-sm-2">
                                         <button type="button" class="btn btn-success w-100"
                                             v-on:click="skopiujDoSchowka('{{ config('app.kontoBankowe') }}')">Kopiuj</button>
                                     </div>
                                 </div>
                                 <div class="row align-items-end">
                                     <div class="col-12 col-sm-9 pe-sm-2 mb-3 mb-sm-0">
                                         <label for="tytulPrzelewu" class="mb-2">Tytuł przelewu</label>
                                         <input type="text" class="form-control" id="tytulPrzelewu" name="tytulPrzelewu"
                                             ref="tytulPrzelewu" readonly
                                             value="{{ config('app.tytulPrzelewuWplatyPrefiks') . $uzytkownik->id }}">
                                     </div>
                                     <div class="col-12 col-sm-3 ps-sm-2">
                                         <button type="button" class="btn btn-success w-100"
                                             v-on:click="skopiujDoSchowka('{{ config('app.tytulPrzelewuWplatyPrefiks') . $uzytkownik->id }}')">Kopiuj</button>
                                     </div>
                                 </div>
                             </div>

                         </div>
                     </div>
                 </div>

                 {{-- Bloki wypłaty PLN --}}

                 <div class="col-12 col-lg-6 mb-3">
                     <div class="border rounded-top border-start border-end border-bottom rounded-bottom h-100">
                         <form class="mb-0" action="{{ route('wyplacPln') }}" method="POST"
                             v-on:submit="walidujOrazWyslijFormularz">
                             @csrf
                             <div class="row g-0">
                                 <div class="col-12">
                                     <div class="niebieskie-tlo py-3 rounded-top text-center text-light">Wypłata PLN
                                     </div>
                                 </div>
                             </div>
                             <div class="row pb-4 pt-3 px-2 px-sm-4 g-0">
                                 <div class="col-12">
                                     <div class="row mb-3">
                                         <div class="col-12">
                                             <label for="kwotaWyplatyPln" class="mb-2">Kwota PLN</label>
                                             <input type="text" class="form-control lekko-niebieskie-tlo"
                                                 name="kwotaWyplatyPln" v-model="kwotaWyplatyPln"
                                                 v-on:input="wyliczPlnDoPobrania" required>
                                         </div>
                                     </div>
                                     <div class="row mb-3">
                                         <div class="col-12">
                                             <label for="numerKontaDoWyplaty" class="mb-2">Konto
                                                 bankowe</label>
                                             <input type="text" class="form-control lekko-niebieskie-tlo"
                                                 name="numerKontaDoWyplaty" v-model="numerKontaDoWyplaty" v-on:input="formatujNumerKontaDoWyplaty" required>
                                         </div>
                                     </div>
                                     <div class="row mb-3">
                                         <div class="col-12">
                                             <p v-on:click="wyplacWszystko()" class="m-0 kursorLapki">
                                                 Wypłać
                                                 wszystko</p>
                                         </div>
                                     </div>
                                     <div class="row">
                                         <div class="col-12">
                                             <p class="m-0">Pobierzemy: <span
                                                     v-cloak>@{{ plnDoPobraniaSformatowane }}</span> PLN
                                             </p>
                                         </div>
                                     </div>
                                     <div class="row">
                                         <div class="col-12">
                                             <div class="row mt-3">
                                                 <div class="col-12">
                                                     <button type="submit" class="btn btn-success w-100"
                                                         v-bind:disabled='trwaWyplacaniePln'>Zatwierdź
                                                     </button>
                                                 </div>
                                             </div>
                                         </div>
                                     </div>
                                 </div>
                             </div>
                         </form>
                     </div>
                 </div>

                 {{-- Blok historii wpłat i wypłat --}}

                 <div class="col-12">
                     <div class="border rounded-top border-start border-end border-bottom rounded-bottom h-100">
                         <div class="row g-0">
                             <div class="col-12">
                                 <div class="niebieskie-tlo p-3 rounded-top text-center text-light">Historia wpłat i
                                     wypłat
                                 </div>
                             </div>
                         </div>
                         <form class="mb-0" ref="formularzAnulowaniaWyplatyPln" action="" method="POST">
                             @csrf
                             <div class="table-responsive">
                                 <table class="table align-middle mb-0">
                                     <thead class="bezowe-tlo przyklejony-naglowek-tabeli">
                                         <tr>
                                             <th scope="col">ID</th>
                                             <th scope="col">Rodzaj</th>
                                             <th scope="col" class="text-center">Kwota PLN</th>
                                             <th scope="col" class="text-center">Prowizja PLN</th>
                                             <th scope="col">Konto bankowe</th>
                                             <th scope="col">Status</th>
                                             <th scope="col" class="text-center">Data</th>
                                             <th scope="col"></th>
                                         </tr>
                                     </thead>
                                     <tbody>
                                         @foreach ($wplatyOrazWyplatyUzytkownika as $wplataLubWyplata)
                                             <tr>
                                                 <th scope="row">{{ $wplataLubWyplata['id'] }}</th>
                                                 <td class="@if ($wplataLubWyplata['typ'] === 'Wpłata') text-success @elseif ($wplataLubWyplata['typ'] === 'Wypłata') text-danger @endif">
                                                     {{ $wplataLubWyplata['typ'] }}</td>
                                                 <td class="text-center">{{ $wplataLubWyplata['kwota_pln'] }}</td>
                                                 <td class="text-center">{{ $wplataLubWyplata['prowizja'] }}</td>
                                                 <td>{{ $wplataLubWyplata['konto_bankowe'] }}</td>
                                                 <td
                                                     class="
                                                  @switch($wplataLubWyplata['status'])
                                                      @case('Zlecona') 
                                                          text-primary 
                                                      @break
                                                      @case('Realizowana')
                                                          text-primary
                                                      @break
                                                      @case('Anulowana')
                                                          text-secondary
                                                      @break
                                                      @case('Wysłana')
                                                          text-success
                                                      @break
                                                      @case('Zakończona')
                                                          text-success
                                                      @break
                                                  @endswitch
                                                  ">
                                                     {{ $wplataLubWyplata['status'] }}
                                                 </td>
                                                 <td class="text-center">{{ $wplataLubWyplata['created_at'] }}</td>
                                                 <td>
                                                     @if ($wplataLubWyplata['typ'] === 'Wypłata' && $wplataLubWyplata['status'] === 'Zlecona')
                                                         <button type="button" class="btn btn-link p-0"
                                                             v-on:click="potwierdzenieAnulowaniaWyplatyPln({{ $wplataLubWyplata['id'] }})"><i
                                                                 class="fas fa-times"></i></button>
                                                     @endif
                                                 </td>
                                             </tr>
                                         @endforeach
                                     </tbody>
                                 </table>
                             </div>
                         </form>
                     </div>
                 </div>

             </div>



             @include('layouts.pasekPaginacji')
         </div>
     </div>
 @endsection
