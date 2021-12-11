{{-- Przekroczono czas odpowiedzi serwera --}}

@extends('layouts.app')

@section('content')

    <div class="container mt-3 mt-md-3 mt-xl-4">
        <div class="row" id="komunikat">
            <div class="col-12">
                @include('layouts.alert',['bladHttp' => "Przekroczono czas odpowiedzi serwera"])
            </div>
        </div>
    </div>

@endsection
