  <nav class="niebieskie-tlo navbar navbar-expand-md navbar-dark" aria-label="Pasek nawigacji">
      <div class="container">
          {{-- <img class="align-self-center me-3" src="{{asset("images/global/logo.png")}}" alt="Logo serwisu" width="48" height="28"> --}}
          <a class="navbar-brand" href="{{ route('glowna') }}"><span>Bit</span>Kantor.pl</a>
          <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse"
              data-bs-target="#gornyPasekNawigacji" aria-controls="gornyPasekNawigacji" aria-expanded="false"
              aria-label="Toggle navigation">
              <span class="navbar-toggler-icon"></span>
          </button>

          <div class="collapse navbar-collapse" id="gornyPasekNawigacji">
              <ul class="navbar-nav ms-auto">
                  @can('uprawnieniaAdmina', Auth::user())
                      <li class="nav-item">
                          <a class="nav-link @isset($aktywneMenu) @if ($aktywneMenu === 'panelAdmina') active @endif @endisset" aria-current="page"
                              href="{{ route('panelAdmina') }}">Administracja</a>
                      </li>
                  @endcan
                  <li class="nav-item">
                      <a class="nav-link @isset($aktywneMenu) @if ($aktywneMenu === 'index') active @endif @endisset" aria-current="page"
                          href="{{ route('glowna') }}">Wymiana</a>
                  </li>
                  <li class="nav-item">
                      <a class="nav-link @isset($aktywneMenu) @if ($aktywneMenu === 'pomoc') active @endif @endisset" href="#">Pomoc</a>
                  </li>
                  <li class="nav-item dropdown">
                      @auth
                          <a class="nav-link @isset($aktywneMenu) @if ($aktywneMenu === 'mojeKonto') active @endif @endisset dropdown-toggle"
                              href="#" id="menuRozwijane" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                              Moje konto
                          </a>
                          <ul class="dropdown-menu lekko-niebieskie-tlo mt-md-2" aria-labelledby="menuRozwijane">
                              <li><a class="dropdown-item" href="{{ route('saldoPln') }}">Saldo PLN</a></li>
                              <li><a class="dropdown-item" href="{{ route('saldoBtc') }}">Saldo BTC</a></li>
                              <li><a class="dropdown-item" href="{{ route('historiaTransakcji') }}">Historia
                                      transakcji</a></li>
                              <li><a class="dropdown-item" href="#">Bezpieczeństwo</a></li>
                              <li><a class="dropdown-item" href="#">Moje dane</a></li>
                              <li>
                                  <hr class="dropdown-divider">
                              </li>
                              <li><a class="dropdown-item" href="{{ route('wyloguj') }}">Wyloguj</a></li>
                          </ul>
                      @endauth
                      @guest
                          <a class="nav-link @isset($aktywneMenu) @if ($aktywneMenu === 'rejestracja') active @endif @endisset"
                              href="{{ route('rejestracja') }}">Rejestracja</a>
                      @endguest
                  </li>
                  @guest
                      <li class="nav-item">
                          {{-- @auth
                          <a class="nav-link" href="{{ route('wyloguj') }}">Wyloguj</a>
                      @endauth --}}
                          <a class="nav-link @isset($aktywneMenu) @if ($aktywneMenu === 'logowanie') active @endif @endisset"
                              href="{{ route('login') }}">Logowanie</a>
                      </li>
                  @endguest
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
