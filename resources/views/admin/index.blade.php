@extends('layouts.app', ['aktywneMenu' => "panelAdmina"])
@section('content')
    @include('layouts.modals.potwierdzenieAkcji')
    @include('layouts.toasts.komunikat')
    @include('layouts.admin.dolnyPasekNawigacji', ['aktywneDolneMenu' => "historiaTransakcji"])
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
            </div>
        </div>
        {{-- @include('layouts.pasekPaginacji') --}}
    </div>
@endsection
