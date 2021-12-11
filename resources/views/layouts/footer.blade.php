<nav class="niebieskie-tlo navbar navbar-expand-md navbar-dark mb-0" aria-label="Pasek nawigacji">
    <div class="container">
        {{-- <img class="align-self-center me-3" src="{{asset("images/global/logo.png")}}" alt="Logo serwisu" width="48" height="28"> --}}
        <a class="navbar-brand" href="{{ route('glowna') }}"><span>Bit</span>Kantor.pl</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#pasekNawigacji"
            aria-controls="pasekNawigacji" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="pasekNawigacji">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="{{ route('glowna') }}">Wymiana</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Pomoc</a>
                </li>
                <li class="nav-item">
                    @auth
                        <a class="nav-link" href="{{ route('rejestracja') }}">Moje konto</a>
                    @endauth
                    @guest
                        <a class="nav-link" href="{{ route('rejestracja') }}">Rejestracja</a>
                    @endguest
                </li>
                <li class="nav-item">
                    @auth
                        <a class="nav-link" href="{{ route('wyloguj') }}">Wyloguj</a>
                    @endauth
                    @guest
                        <a class="nav-link" href="{{ route('login') }}">Logowanie</a>
                    @endguest
                </li>
                {{-- <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="dropdown07" data-bs-toggle="dropdown" aria-expanded="false">Dropdown</a>
          <ul class="dropdown-menu" aria-labelledby="dropdown07">
            <li><a class="dropdown-item" href="#">Action</a></li>
            <li><a class="dropdown-item" href="#">Another action</a></li>
            <li><a class="dropdown-item" href="#">Something else here</a></li>
          </ul>
        </li> --}}
            </ul>
            {{-- <form>
        <input class="form-control" type="text" placeholder="Search" aria-label="Search">
      </form> --}}
        </div>
    </div>
</nav>
