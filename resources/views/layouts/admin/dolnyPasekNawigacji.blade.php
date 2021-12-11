<nav class="navbar dolny py-0 navbar-expand-md navbar-light" aria-label="Pasek nawigacji">
    <div class="container justify-content-end my-2">
        <div class="row">
            <div class="col-auto order-md-last">
                <div class="collapse navbar-collapse" id="dolnyPasekNawigacji">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link @if ($aktywneDolneMenu === 'wyplatyPln') active @endif" aria-current="page"
                                href="{{ route('wyplatyPln') }}">Wypłaty PLN</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link @if ($aktywneDolneMenu === 'wyplatyBtc') active @endif" aria-current="page"
                                href="{{ route('wyplatyBtc') }}">Wypłaty BTC</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="col-auto order-md-first">
                <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse"
                    data-bs-target="#dolnyPasekNawigacji" aria-controls="dolnyPasekNawigacji" aria-expanded="false"
                    aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
        </div>
    </div>
</nav>
