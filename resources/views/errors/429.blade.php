{{-- Przekroczono limit zapytań --}}

@extends('layouts.app')

@section('content')

    <div class="container mt-3 mt-md-3 mt-xl-4">
        <div class="row" id="komunikat">
            <div class="col-12">
                @include('layouts.alert',['bladHttp' => "Przekroczono limit zapytań z Twojego adresu IP"])
            </div>
        </div>
    </div>

@endsection
