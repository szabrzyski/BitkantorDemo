 @extends('layouts.app', ['aktywneMenu' => "mojeKonto"])
 @push('js')
     <script>
         const minimalnaKwotaWyplatyBtc = {!! json_encode(config('app.minimalnaKwotaWyplatyBtc'), JSON_HEX_TAG) !!};
         const prowizjaZaWyplateBtc = {!! json_encode(config('app.prowizjaZaWyplateBtc'), JSON_HEX_TAG) !!};
     </script>
     <script src="{{ asset('js/libs/PomocnikLiczbowy.js') }}"></script>
     <script src="{{ asset('js/saldoBtc.js') }}"></script>
 @endpush
 @section('content')
     <div id="saldoBtc">
         @include('layouts.modals.potwierdzenieAkcji')
         @include('layouts.toasts.komunikat')
         @include('layouts.dolnyPasekNawigacji', ['aktywneDolneMenu' => "saldoBtc"])
         <div class="container-fluid border-bottom">
         </div>
         <div class="container mt-3 mt-md-3 mt-xl-4">
             <div class="row" id="komunikat">
                 <div class="col-12">
                     @include('layouts.alert')
                 </div>
             </div>
             <div class="row mt-2 mt-md-2 mt-xl-3 mb-4 gx-4 gy-2">

                 {{-- Bloki wpłaty i wypłaty BTC --}}

                 <div class="col-12 col-lg-6 mb-3">
                     <div class="border rounded-top border-start border-end border-bottom rounded-bottom h-100">
                         <div class="row g-0">
                             <div class="col-12">
                                 <div class="niebieskie-tlo py-3 rounded-top text-center text-light">Wpłata BTC</div>
                             </div>
                         </div>
                         <div class="row pb-4 pt-3 px-2 px-sm-4 g-0">
                             <div class="col-12">
                                 <div class="row mb-3 align-items-end">
                                     <div class="col-12 col-md-9 pe-md-2 mb-3 mb-md-0">
                                         <label for="adresPortfelaDoWplaty" class="mb-2">Adres
                                             portfela</label>
                                         <input type="text" class="form-control" id="adresPortfelaDoWplaty"
                                             name="adresPortfelaDoWplaty" ref="adresPortfelaDoWplaty" readonly
                                             value="{{ $uzytkownik->adres_portfela }}">
                                     </div>
                                     <div class="col-12 col-md-3 ps-md-2">
                                         <button type="button" class="btn btn-success w-100"
                                             v-on:click="skopiujDoSchowka('{{ $uzytkownik->adres_portfela }}')">Kopiuj</button>
                                     </div>
                                 </div>
                                 <div class="row">
                                     <div class="col-12">
                                         <p class="mb-0">Wpłata zostanie zaksięgowana po otrzymaniu <b>6</b>
                                             potwierdzeń.
                                         </p>
                                     </div>
                                 </div>
                             </div>

                         </div>
                     </div>
                 </div>

                 <div class="col-12 col-lg-6 mb-3">
                     <div class="border rounded-top border-start border-end border-bottom rounded-bottom h-100">
                         <form class="mb-0" action="{{ route('wyplacBtc') }}" method="POST"
                             v-on:submit="walidujOrazWyslijFormularz">
                             @csrf
                             <div class="row g-0">
                                 <div class="col-12">
                                     <div class="niebieskie-tlo py-3 rounded-top text-center text-light">Wypłata BTC
                                     </div>
                                 </div>
                             </div>
                             <div class="row pb-4 pt-3 px-2 px-sm-4 g-0">
                                 <div class="col-12">
                                     <div class="row mb-3">
                                         <div class="col-12">
                                             <label for="kwotaWyplatyBtc" class="mb-2">Kwota BTC</label>
                                             <input type="text" class="form-control lekko-niebieskie-tlo"
                                                 name="kwotaWyplatyBtc" v-model="kwotaWyplatyBtc"
                                                 v-on:input="wyliczBtcDoPobrania" required>
                                         </div>
                                     </div>
                                     <div class="row mb-3">
                                         <div class="col-12">
                                             <label for="adresPortfelaDoWyplaty" class="mb-2">Adres
                                                 portfela</label>
                                             <input type="text" class="form-control lekko-niebieskie-tlo"
                                                 name="adresPortfelaDoWyplaty" v-model="adresPortfelaDoWyplaty" v-on:input="formatujAdresPortfelaDoWyplaty" required>
                                         </div>
                                     </div>
                                     <div class="row mb-3">
                                         <div class="col-12">
                                             <p v-on:click="wyplacWszystko()" class="m-0 kursorLapki">
                                                 Wypłać
                                                 wszystko</p>
                                         </div>
                                     </div>
                                     <div class="row mb-3">
                                         <div class="col-12">
                                             <p class="m-0">Pobierzemy: <span
                                                     v-cloak>@{{ btcDoPobraniaSformatowane }}</span> BTC
                                             </p>
                                         </div>
                                     </div>
                                     <div class="row">
                                         <div class="col-12">
                                             <button type="submit" class="btn btn-success w-100"
                                                 v-bind:disabled='trwaWyplacanieBtc'>Zatwierdź
                                             </button>
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
                         <form class="mb-0" ref="formularzAnulowaniaWyplatyBtc" action="" method="POST">
                             @csrf
                             <div class="table-responsive">
                                 <table class="table align-middle mb-0">
                                     <thead class="bezowe-tlo przyklejony-naglowek-tabeli">
                                         <tr>
                                             <th scope="col">ID</th>
                                             <th scope="col">Rodzaj</th>
                                             <th scope="col" class="text-center">Kwota BTC</th>
                                             <th scope="col" class="text-center">Prowizja BTC</th>
                                             <th scope="col">Adres docelowy</th>
                                             <th scope="col">TxID</th>
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
                                                 <td class="text-center">{{ $wplataLubWyplata['kwota_btc'] }}</td>
                                                 <td class="text-center">{{ $wplataLubWyplata['prowizja'] }}</td>
                                                 <td>{{ $wplataLubWyplata['adres_docelowy'] }}</td>
                                                 <td>
                                                     @if ($wplataLubWyplata['tx_id'])
                                                         {{ Str::limit($wplataLubWyplata['tx_id'], 15) }} <button
                                                             type="button" class="btn btn-link p-0"
                                                             v-on:click="skopiujDoSchowka('{{ $wplataLubWyplata['tx_id'] }}')"><i
                                                                 class="fas fa-copy" data-bs-toggle="tooltip"
                                                                 data-bs-placement="right"
                                                                 title="{{ $wplataLubWyplata['tx_id'] }}"></i></button>
                                                     @endif
                                                 </td>
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
                                                             v-on:click="potwierdzenieAnulowaniaWyplatyBtc({{ $wplataLubWyplata['id'] }})"><i
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
