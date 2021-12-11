@extends('layouts.app', ['aktywneMenu' => "mojeKonto"])
@section('content')
    @include('layouts.modals.potwierdzenieAkcji')
    @include('layouts.toasts.komunikat')
    @include('layouts.dolnyPasekNawigacji', ['aktywneDolneMenu' => "historiaTransakcji"])
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
                            <div class="niebieskie-tlo p-3 rounded-top text-center text-light">Historia
                                transakcji
                            </div>
                        </div>
                    </div>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="bezowe-tlo przyklejony-naglowek-tabeli">
                                    <tr>
                                        <th scope="col">ID</th>
                                        <th scope="col">Rodzaj</th>
                                        <th scope="col">Kurs PLN</th>
                                        <th scope="col">Kwota BTC</th>
                                        <th scope="col">Kwota PLN</th>
                                        <th scope="col">Prowizja</th>
                                        <th scope="col" class="text-center">Data</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($transakcjeUzytkownika as $transakcja)
                                        <tr>
                                            <th scope="row">{{ $transakcja['id'] }}</th>
                                            <td class="@if ($transakcja['typ_transakcji'] === 'Zakup') text-success @elseif ($transakcja['typ_transakcji'] === 'Sprzedaż') text-danger @endif">
                                                {{ $transakcja['typ_transakcji'] }}</td>
                                            <td>{{ $transakcja['kurs'] }}</td>
                                            <td>{{ $transakcja['kwota_btc'] }}</td>
                                            <td>{{ $transakcja['kwota_pln'] }}</td>
                                            <td>{{ $transakcja['prowizja'] }}</td>
                                            <td class="text-center">{{ $transakcja['created_at'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                </div>
            </div>
        </div>
        @include('layouts.pasekPaginacji')
    </div>
@endsection
