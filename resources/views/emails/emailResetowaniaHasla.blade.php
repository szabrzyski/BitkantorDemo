@component('mail::message')
# Resetowanie hasła

Kliknij poniższy przycisk, aby zresetować hasło w serwisie. Link straci ważność po 24 godzinach.

@component('mail::button', ['url' => route('dokonczResetHasla',$kodWeryfikacyjny)])
Zresetuj hasło
@endcomponent

Pozdrawiamy,<br>
Zespół Bitkantor.pl
@endcomponent