<?php

namespace App\Policies;

use App\Models\WyplataBtc;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class WyplataBtcPolicy
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
     * Czy użytkownik może wypłacić BTC
     *
     * @param  \App\Models\User $uzytkownik
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function wyplacBtc(User $uzytkownik)
    {
        return $uzytkownik->zweryfikowany && !$uzytkownik->zablokowany;
    }

    /**
     * Czy użytkownik może anulować wypłatę BTC
     *
     * @param  \App\Models\User $uzytkownik
     * @param  \App\Models\WyplataBtc $wyplataBtc
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function anulujWyplateBtc(User $uzytkownik, WyplataBtc $wyplataBtc)
    {
        return $uzytkownik->id === $wyplataBtc->uzytkownik_id;
    }

}
