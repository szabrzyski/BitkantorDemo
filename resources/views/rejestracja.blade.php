@extends('layouts.app', ['aktywneMenu' => "rejestracja"])
@push('js')
    <script>
        const email = @if (old('email')) {!! json_encode(old('email'), JSON_HEX_TAG) !!} @else "" @endif;
        const haslo = @if (old('haslo')) {!! json_encode(old('haslo'), JSON_HEX_TAG) !!} @else "" @endif;
        const regulamin = @if (old('regulamin')) true @else false @endif;
    </script>
    <script src="{{ asset('js/rejestracja.js') }}"></script>
@endpush
@section('content')
    <div class="container mt-3 mt-md-3 mt-xl-4">
        <div class="row justify-content-center" id="komunikat">
            <div class="col-11 col-md-10 col-lg-8 col-xl-7 col-xxl-6">
                @include('layouts.alert')
            </div>
        </div>
        <div class="row mt-3 mt-md-3 mt-xl-4 justify-content-center">
            <div id="formularzRejestracji" class="col-11 col-md-10 col-lg-8 col-xl-7 col-xxl-6">
                <div class="border rounded-top border-start border-end border-bottom rounded-bottom h-100">
                    <form class="mb-0" action="{{ route('zarejestruj') }}" method="POST"
                        v-on:submit="walidujOrazWyslijFormularz">
                        @csrf
                        <div class="row g-0">
                            <div class="col-12">
                                <div class="niebieskie-tlo p-3 rounded-top text-center text-light">Załóż nowe konto
                                </div>
                            </div>
                        </div>
                        <div class="row py-4 px-2 g-0">
                            <div class="col-12 ">
                                <div class="row pt-2 pb-2 justify-content-center text-center">
                                    <div class="col-11">
                                        <input type="email" class="form-control lekko-niebieskie-tlo" minlength="1"
                                            maxlength="255" name="email" id="email" placeholder="Adres e-mail"
                                            aria-label="Adres e-mail" required v-model="email">
                                        <p class="text-danger m-0 text-end">@error('email')
                                                {{ $message }}
                                            @enderror</p>
                                    </div>
                                </div>
                                <div class="row pt-4 justify-content-center text-center">
                                    <div class="col-11">
                                        <input type="password" class="form-control lekko-niebieskie-tlo" minlength="8"
                                            maxlength="255" name="haslo" id="haslo" placeholder="Hasło" aria-label="Hasło"
                                            required v-model="haslo">
                                        <p class="text-danger m-0 text-end">@error('haslo')
                                                {{ $message }}
                                            @enderror</p>
                                    </div>
                                </div>
                                <div class="row py-4 justify-content-center text-center">
                                    <div class="col-11 text-end">
                                        <label class="form-check-label me-2" for="regulamin">
                                            Akceptuję regulamin serwisu
                                        </label>
                                        <input class="form-check-input" type="checkbox" id="regulamin" name="regulamin"
                                            required v-model="regulamin">
                                        <p class="text-danger m-0 text-end">@error('regulamin')
                                                {{ $message }}
                                            @enderror</p>
                                    </div>
                                </div>
                                <div class="row pb-2 justify-content-center">
                                    <div class="col-11">
                                        <button type="submit" class="btn btn-success w-100">Zatwierdź</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
