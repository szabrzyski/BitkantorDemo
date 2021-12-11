@extends('layouts.app')
@section('content')
    @include('layouts.admin.navigationBar')
    <div class="container mt-4">
        <div class="row mb-4" id="komunikat">
            <div class="col-12">
                @include('layouts.alert')
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-12">
                <a class="btn btn-primary" href="{{ route('users.index') }}" role="button">Powrót do listy
                    użytkowników</a>
            </div>
        </div>
        <div class="row">
            {{-- Dane użytkownika --}}
            <div class="col-6">
                <div class="row px-1 pt-1 justify-content-center">
                    <div class="col-12">
                        <label for="id" class="mb-2">ID:</label>
                        <input type="text" class="form-control lekko-niebieskie-tlo" name="id" value="{{ $user->id }}"
                            placeholder="" readonly>
                    </div>
                </div>
                <div class="row px-1 pt-3 justify-content-center">
                    <div class="col-12">
                        <label for="email" class="mb-2">Adres e-mail:</label>
                        <input type="email" class="form-control lekko-niebieskie-tlo" name="email"
                            value="{{ $user->email }}" placeholder="" readonly>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
