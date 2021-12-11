<nav class="navbar mt-2 mb-1 my-md-2 dolny py-0 navbar-expand-md navbar-light" aria-label="Pasek nawigacji">
    <div class="container justify-content-center">
        <div class="row w-100">
            <div class="ps-0 col-auto col-md-12 col-xl-auto ms-xl-0 ps-xl-0 pe-0">
                <div id="dolnyPasekNawigacji" class="collapse navbar-collapse justify-content-end">
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link ps-0 @if ($aktywneDolneMenu === 'saldoPln') active @endif" aria-current="page"
                                href="{{ route('saldoPln') }}">Saldo PLN</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link @if ($aktywneDolneMenu === 'saldoBtc') active @endif" href="{{ route('saldoBtc') }}">Saldo BTC</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link @if ($aktywneDolneMenu === 'historiaTransakcji') active @endif"
                                href="{{ route('historiaTransakcji') }}">Historia
                                transakcji</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link @if ($aktywneDolneMenu === 'bezpieczenstwo') active @endif"
                                href="{{ route('historiaTransakcji') }}">Bezpieczeństwo</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link pe-0 @if ($aktywneDolneMenu === 'mojeDane') active @endif"
                                href="{{ route('historiaTransakcji') }}">Moje dane</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="col-auto ms-auto pe-0 mt-1 mb-2 d-block d-md-none">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#dolnyPasekNawigacji" aria-controls="dolnyPasekNawigacji" aria-expanded="false"
                    aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
            <nav class="col-xl-auto ms-auto navbar dolny py-0 text-end navbar-expand-sm navbar-light pe-0"
                aria-label="Pasek sald">
                <div class="container pe-0">
                    <div id="pasekSald" class="col-12 pe-0 justify-content-end navbar-nav">
                        <ul class="navbar-nav">
                            <li class="nav-item">
                                <a class="nav-link border-sm-end py-0 mb-xl-0 mb-1 mb-md-2">Saldo PLN:
                                    <span ref="saldoPln">{{ $uzytkownik->saldoPlnSformatowane() }}</span></a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-0 mb-xl-0 mb-1 mb-md-2 pe-0">Saldo BTC:
                                    <span ref="saldoBtc">{{ $uzytkownik->saldoBtcSformatowane() }}</span></a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
        </div>
    </div>
</nav>
