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
            {{-- Dodawanie użytkownika --}}
            <div class="col-6">
                <form id="formularzDodawaniaUzytkownika" action="{{ route('users.store') }}" method="POST">
                    @csrf
                    <div class="row px-1 pt-1 justify-content-center">
                        <div class="col-12">
                            <label for="email" class="mb-2">Adres e-mail:</label>
                            <input type="email" class="form-control lekko-niebieskie-tlo" name="email"
                                value="{{ old('email') }}" placeholder="">
                                <p class="text-danger m-0 text-end">@error('email')
                                    {{ $message }}
                                @enderror</p>
                        </div>
                    </div>
                    <div class="row px-1 pt-3 justify-content-center">
                        <div class="col-12">
                            <label for="password" class="mb-2">Hasło:</label>
                            <input type="password" class="form-control lekko-niebieskie-tlo" name="password"
                                value="{{ old('password') }}" placeholder="">
                                <p class="text-danger m-0 text-end">@error('password')
                                    {{ $message }}
                                @enderror</p>
                        </div>
                    </div>
                    {{-- <div class="row px-1 pt-3 justify-content-center">
                            <div class="col-12">
                                <label for="plec" class="mb-2">Płeć</label>
                                <select class="form-select lekko-niebieskie-tlo" id="plec">
                                    <option selected>Mężczyzna</option>
                                </select>
                            </div>
                        </div> --}}
                    <div class="row px-1 py-3 pt-4">
                        <div class="col-12">
                            <button type="submit" class="btn btn-success w-100">Dodaj nowego użytkownika</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
