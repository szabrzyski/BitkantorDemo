@if (isset($bladHttp))
    <div class="alert alert-danger mb-0">
        {{ $bladHttp }}
    </div>
@elseif (session()->has('sukces'))
    <div class="alert alert-success mb-0">
        {{ session()->get('sukces') }}
    </div>
@elseif (session()->has('blad'))
    <div class="alert alert-danger mb-0">
        {{ session()->get('blad') }}
    </div>
@elseif (session()->has('informacja'))
    <div class="alert alert-primary mb-0">
        {{ session()->get('informacja') }}
    </div>
@endif
