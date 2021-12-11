<?php

namespace App\Policies;

use App\Models\WyplataPln;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class WyplataPlnPolicy
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
     * Czy użytkownik może wypłacić PLN
     *
     * @param  \App\Models\User $uzytkownik
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function wyplacPln(User $uzytkownik)
    {
        return $uzytkownik->zweryfikowany && !$uzytkownik->zablokowany;
    }

    /**
     * Czy użytkownik może anulować wypłatę PLN
     *
     * @param  \App\Models\User $uzytkownik
     * @param  \App\Models\WyplataPln $wyplataPln
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function anulujWyplatePln(User $uzytkownik, WyplataPln $wyplataPln)
    {
        return $uzytkownik->id === $wyplataPln->uzytkownik_id;
    }

}
