<html lang="pl-PL">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Tytuł strony -->
    <title>
        BitKantor
    </title>
    <!-- CSS -->
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <!-- Ikony -->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/favicons/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicons/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicons/favicon-16x16.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
</head>

<body>
    <!-- Nagłówek -->
    @empty($aktywneMenu) @php $aktywneMenu = null @endphp @endempty
    @include('layouts.header', ['aktywneMenu' => $aktywneMenu])
    <!-- Zawartość strony -->
    @yield('content')
    <!-- Stopka -->
    <footer>
        {{-- @include('layouts.footer') --}}
    </footer>
    {{-- Skrypty JS --}}
    <script src="{{ asset('js/libs/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/libs/axios.min.js') }}"></script>
    <script src="{{ asset('js/libs/vue.global.js') }}"></script>
    <script src="{{ asset('js/globalne.js') }}"></script>
    {{-- <script src="{{ asset('js/vue.global.prod.js') }}"></script> --}}
    @stack('js')
</body>

</html>
