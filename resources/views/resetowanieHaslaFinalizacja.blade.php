@extends('layouts.app', ['aktywneMenu' => "logowanie"])
@push('js')
    <script>
        const haslo = @if (old('haslo')) {!! json_encode(old('haslo'), JSON_HEX_TAG) !!} @else "" @endif;
        const powtorzoneHaslo = @if (old('powtorzoneHaslo')) {!! json_encode(old('powtorzoneHaslo'), JSON_HEX_TAG) !!} @else "" @endif;
    </script>
    <script src="{{ asset('js/resetowanieHaslaFinalizacja.js') }}"></script>
@endpush
@section('content')
    <div class="container mt-3 mt-md-3 mt-xl-4">
        <div class="row justify-content-center" id="komunikat">
            <div class="col-11 col-md-10 col-lg-8 col-xl-7 col-xxl-6">
                @include('layouts.alert')
            </div>
        </div>
        <div class="row mt-3 mt-md-3 mt-xl-4 justify-content-center">
            <div id="formularzFinalizacjiResetowaniaHasla" class="col-11 col-md-10 col-lg-8 col-xl-7 col-xxl-6">
                <div class="border rounded-top border-start border-end border-bottom rounded-bottom h-100">
                    <form class="mb-0" action="{{ route('zresetujHaslo') }}" method="POST"
                        v-on:submit="walidujOrazWyslijFormularz">
                        @csrf
                        <input type="hidden" id="kodWeryfikacyjny" name="kodWeryfikacyjny"
                            value="{{ $kodWeryfikacyjny }}">
                        <div class="row g-0">
                            <div class="col-12">
                                <div class="niebieskie-tlo p-3 rounded-top text-center text-light">Wprowadź nowe hasło
                                </div>
                            </div>
                        </div>
                        <div class="row py-4 px-2 g-0">
                            <div class="col-12">
                                <div class="row pt-2 pb-2 justify-content-center text-center">
                                    <div class="col-11">
                                        <input type="password" class="form-control lekko-niebieskie-tlo" minlength="8"
                                            maxlength="255" name="haslo" id="haslo" placeholder="Hasło" aria-label="Hasło"
                                            required v-model="haslo">
                                        <p class="text-danger m-0 text-end">@error('haslo')
                                                {{ $message }}
                                            @enderror</p>
                                    </div>
                                </div>
                                <div class="row pt-4 pb-2 justify-content-center text-center">
                                    <div class="col-11">
                                        <input type="password" class="form-control lekko-niebieskie-tlo" minlength="8"
                                            maxlength="255" name="powtorzoneHaslo" id="powtorzoneHaslo" placeholder="Hasło"
                                            aria-label="Hasło" required v-model="powtorzoneHaslo">
                                        <p class="text-danger m-0 text-end">@error('powtorzoneHaslo')
                                                {{ $message }}
                                            @enderror</p>
                                    </div>
                                </div>
                                <div class="row pt-4 pb-2 justify-content-center">
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
