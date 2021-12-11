@component('mail::message')
# Aktywacja konta

Kliknij poniższy przycisk, aby dokończyć rejestrację w serwisie.

@component('mail::button', ['url' => route('aktywuj',$kodWeryfikacyjny)])
Aktywuj konto
@endcomponent

Pozdrawiamy,<br>
Zespół Bitkantor.pl
@endcomponent