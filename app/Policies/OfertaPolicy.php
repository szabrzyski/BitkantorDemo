<?php

namespace App\Policies;

use App\Models\Oferta;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OfertaPolicy
{
    use HandlesAuthorization;

    /**
     * Preautoryzacja
     *
     * @param  \App\Models\User $uzytkownik
     * @return void|bool
     */
    public function before(User $uzytkownik)
    {
        if ($uzytkownik->jest_adminem) {
            return true;
        }
    }

    /**
     * Czy użytkownik może wystawić ofertę
     *
     * @param  \App\Models\User $uzytkownik
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function wystawOferte(User $uzytkownik)
    {
        return $uzytkownik->zweryfikowany && !$uzytkownik->zablokowany;
    }

    /**
     * Czy użytkownik może anulować ofertę
     *
     * @param  \App\Models\User $uzytkownik
     * @param  \App\Models\Oferta $oferta
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function anulujOferte(User $uzytkownik, Oferta $oferta)
    {
        return $uzytkownik->id === $oferta->wystawiajacy_id;
    }

}
