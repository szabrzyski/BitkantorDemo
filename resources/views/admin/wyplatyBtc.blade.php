@extends('layouts.app', ['aktywneMenu' => "panelAdmina"])
@push('js')
    <script src="{{ asset('js/admin/wyplatyBtc.js') }}"></script>
@endpush
@section('content')
    <div id="wyplatyBtc">
        @include('layouts.modals.potwierdzenieAkcji')
        @include('layouts.modals.admin.formularzRealizacjiWyplatyBtc')
        @include('layouts.toasts.komunikat')
        @include('layouts.admin.dolnyPasekNawigacji', ['aktywneDolneMenu' => "wyplatyBtc"])
        <div class="container-fluid border-bottom">
        </div>
        <div class="container mt-3 mt-md-3 mt-xl-4">
            <div class="row" id="komunikat">
                <div class="col-12">
                    @include('layouts.alert')
                </div>
            </div>
            <div class="row mt-2 mt-md-2 mt-xl-3 mb-4 gx-4 gy-2">
                <div class="col-12">
                    <div class="border rounded-top border-start border-end border-bottom rounded-bottom h-100">
                        <div class="row g-0">
                            <div class="col-12">
                                <div class="niebieskie-tlo p-3 rounded-top text-center text-light">Wypłaty
                                    BTC
                                </div>
                            </div>
                        </div>
                        <form class="mb-0" ref="formularzWyplatyBtc" action="" method="POST">
                            @csrf
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead class="bezowe-tlo przyklejony-naglowek-tabeli">
                                        <tr>
                                            <th scope="col">ID</th>
                                            <th scope="col">ID użytkownika</th>
                                            <th scope="col">Kwota BTC</th>
                                            <th scope="col" class="text-center">Prowizja</th>
                                            <th scope="col">Adres portfela</th>
                                            <th scope="col">TxID</th>
                                            <th scope="col">Status</th>
                                            <th scope="col" class="text-center">Data aktualizacji</th>
                                            <th scope="col" class="text-center">Akcje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($wyplatyBtc as $wyplataBtc)
                                            <tr>
                                                <th scope="row">{{ $wyplataBtc['id'] }}</th>
                                                <td>{{ $wyplataBtc->uzytkownik->id }} <i class="fas fa-user"
                                                        data-bs-toggle="tooltip" data-bs-placement="right"
                                                        title="{{ $wyplataBtc->uzytkownik->email }}"></i></td>
                                                <td>{{ $wyplataBtc->kwota_btc }} <button type="button"
                                                        class="btn btn-link p-0"
                                                        v-on:click="skopiujDoSchowka({{ $wyplataBtc->kwota_btc }})"><i
                                                            class="fas fa-copy"></i></button></td>
                                                <td class="text-center">
                                                    @if ($wyplataBtc->prowizja_btc > 0)
                                                        <i class="fas fa-check-circle text-success" data-bs-toggle="tooltip"
                                                            data-bs-placement="right"
                                                            title="{{ $wyplataBtc->prowizja_btc }} BTC"></i>
                                                    @else
                                                        <i class="fas fa-times-circle text-danger" data-bs-toggle="tooltip"
                                                            data-bs-placement="right"
                                                            title="{{ $wyplataBtc->prowizja_btc }} BTC"></i>
                                                    @endif
                                                </td>

                                                <td>{{ $wyplataBtc->adres_portfela_do_wyplaty }} <button type="button"
                                                        class="btn btn-link p-0"
                                                        v-on:click="skopiujDoSchowka('{{ $wyplataBtc->adres_portfela_do_wyplaty }}')"><i
                                                            class="fas fa-copy"></i></button></td>
                                                <td>
                                                    @if ($wyplataBtc->transakcjaBlockchain)
                                                        {{ Str::limit($wyplataBtc->transakcjaBlockchain->txid, 15) }}
                                                        <button type="button" class="btn btn-link p-0"
                                                            v-on:click="skopiujDoSchowka('{{ $wyplataBtc->transakcjaBlockchain->txid }}')"><i
                                                                class="fas fa-copy" data-bs-toggle="tooltip"
                                                                data-bs-placement="right"
                                                                title="{{ $wyplataBtc->transakcjaBlockchain->txid }}"></i></button>
                                                    @endif
                                                </td>
                                                <td
                                                    class="
                                    @switch($wyplataBtc->status)
                                        @case('Zlecona') 
                                            text-danger 
                                        @break
                                        @case('Realizowana')
                                            text-danger
                                        @break
                                        @case('Anulowana')
                                            text-secondary
                                        @break
                                        @case('Wysłana')
                                            text-primary
                                        @break
                                        @case('Zakończona')
                                            text-success
                                        @break
                                    @endswitch
                                    ">
                                                    {{ $wyplataBtc->status }}
                                                </td>
                                                <td class="text-center">
                                                    {{ $wyplataBtc->dataAktualizacjiSformatowana() }}
                                                </td>
                                                <td class="text-center">
                                                    <button type="button"
                                                        class="btn btn-link ps-0 pe-1 @if (in_array($wyplataBtc->status, ['Anulowana', 'Wysłana', 'Zakończona'])) disabled @endif"
                                                        v-on:click="potwierdzenieAnulowaniaWyplatyBtc({{ $wyplataBtc->id }})"><i
                                                            class="fas fa-times"></i></button>
                                                    <button type="button"
                                                        class="btn btn-link ps-1 pe-1 @if ($wyplataBtc->status !== 'Zlecona') disabled @endif"
                                                        v-on:click="potwierdzenieZablokowaniaWyplatyBtc({{ $wyplataBtc->id }})"><i
                                                            class="fas fa-lock"></i></button>
                                                    <button type="button"
                                                        class="btn btn-link ps-1 pe-0 @if ($wyplataBtc->status !== 'Realizowana') disabled @endif"
                                                        v-on:click="realizacjaWyplatyBtc({{ $wyplataBtc->id }})"><i
                                                            class="fas fa-check-circle"></i></button>
                                                </td>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                    </div>
                </div>
            </div>
        </div>
        @include('layouts.pasekPaginacji')
    </div>
    </div>
@endsection
