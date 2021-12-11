@extends('layouts.app', ['aktywneMenu' => "panelAdmina"])
@push('js')
    <script src="{{ asset('js/admin/wyplatyPln.js') }}"></script>
@endpush
@section('content')
    <div id="wyplatyPln">
        @include('layouts.modals.potwierdzenieAkcji')
        @include('layouts.modals.admin.formularzRealizacjiWyplatyPln')
        @include('layouts.toasts.komunikat')
        @include('layouts.admin.dolnyPasekNawigacji', ['aktywneDolneMenu' => "wyplatyPln"])
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
                                    PLN
                                </div>
                            </div>
                        </div>
                        <form class="mb-0" ref="formularzWyplatyPln" action="" method="POST">
                            @csrf
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead class="bezowe-tlo przyklejony-naglowek-tabeli">
                                        <tr>
                                            <th scope="col">ID</th>
                                            <th scope="col">ID użytkownika</th>
                                            <th scope="col">Kwota PLN</th>
                                            <th scope="col" class="text-center">Prowizja</th>
                                            <th scope="col">Numer konta</th>
                                            <th scope="col">Tytuł przelewu</th>
                                            <th scope="col">Status</th>
                                            <th scope="col" class="text-center">Data aktualizacji</th>
                                            <th scope="col" class="text-center">Akcje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($wyplatyPln as $wyplataPln)
                                            <tr>
                                                <th scope="row">{{ $wyplataPln['id'] }}</th>
                                                <td>{{ $wyplataPln->uzytkownik->id }} <i class="fas fa-user"
                                                        data-bs-toggle="tooltip" data-bs-placement="right"
                                                        title="{{ $wyplataPln->uzytkownik->email }}"></i></td>
                                                <td>{{ $wyplataPln->kwota_pln }} <button type="button"
                                                        class="btn btn-link p-0"
                                                        v-on:click="skopiujDoSchowka({{ $wyplataPln->kwota_pln }})"><i
                                                            class="fas fa-copy"></i></button></td>
                                                <td class="text-center">
                                                    {{ $wyplataPln->prowizja_pln }} PLN
                                                </td>

                                                <td>{{ $wyplataPln->konto_bankowe_odbiorcy }} <button type="button"
                                                        class="btn btn-link p-0"
                                                        v-on:click="skopiujDoSchowka('{{ $wyplataPln->konto_bankowe_odbiorcy }}')"><i
                                                            class="fas fa-copy"></i></button></td>

                                                <td>@if ($wyplataPln->tytul_przelewu) {{ $wyplataPln->tytul_przelewu }} <button type="button"
                                                    class="btn btn-link p-0"
                                                    v-on:click="skopiujDoSchowka('{{ $wyplataPln->tytul_przelewu }}')"><i
                                                        class="fas fa-copy"></i></button> @endif </td>
                                                <td
                                                    class="
                                    @switch($wyplataPln->status)
                                        @case('Zlecona') 
                                            text-danger 
                                        @break
                                        @case('Realizowana')
                                            text-danger
                                        @break
                                        @case('Anulowana')
                                            text-secondary
                                        @break
                                        @case('Zakończona')
                                            text-success
                                        @break
                                    @endswitch
                                    ">
                                                    {{ $wyplataPln->status }}
                                                </td>
                                                <td class="text-center">
                                                    {{ $wyplataPln->dataAktualizacjiSformatowana() }}
                                                </td>
                                                <td class="text-center">
                                                    <button type="button"
                                                        class="btn btn-link ps-0 pe-1 @if (in_array($wyplataPln->status, ['Anulowana', 'Zakończona'])) disabled @endif"
                                                        v-on:click="potwierdzenieAnulowaniaWyplatyPln({{ $wyplataPln->id }})"><i
                                                            class="fas fa-times"></i></button>
                                                    <button type="button"
                                                        class="btn btn-link ps-1 pe-1 @if ($wyplataPln->status !== 'Zlecona') disabled @endif"
                                                        v-on:click="potwierdzenieZablokowaniaWyplatyPln({{ $wyplataPln->id }})"><i
                                                            class="fas fa-lock"></i></button>
                                                    <button type="button"
                                                        class="btn btn-link ps-1 pe-0 @if ($wyplataPln->status !== 'Realizowana') disabled @endif"
                                                        v-on:click="potwierdzenieRealizacjiWyplatyPln({{ $wyplataPln->id }})"><i
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
