<?php

namespace App\Http\Controllers;

use App\Mail\EmailResetowaniaHasla;
use App\Models\ResetowanieHasla;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class ResetowanieHaslaController extends Controller
{
    public function wyslijLinkResetujacy(Request $request)
    {

        $request->validate([
            'email' => 'required|email',
        ],
            [
                'email.*' => 'Nieprawidłowy format adresu e-mail',
            ]);

        if (User::where('email', $request->email)->exists()) {

            $uzytkownik = User::where('email', $request->email)->first();
            $kodWeryfikacyjny = Hash::make($uzytkownik->email);

            $resetHasla = new ResetowanieHasla;
            $resetHasla->email = $uzytkownik->email;
            $resetHasla->kod_weryfikacyjny = $kodWeryfikacyjny;
            $resetHasla->created_at = now();
            $utworzonoResetHasla = $resetHasla->save();

            if ($utworzonoResetHasla) {
                Mail::to($uzytkownik)->send(new EmailResetowaniaHasla(Crypt::encryptString($kodWeryfikacyjny)));
                return redirect()->route('login')->with('sukces', 'Instrukcja resetowania hasła została wysłana na Twój e-mail');
            } else {
                return redirect()->route('zarejestruj')->with('blad', 'Wystąpił błąd podczas resetowania hasła');
            }
        } else {
            // Nie ma takiego użytkownika, ale i tak podajemy fałszywy komunikat
            return redirect()->route('login')->with('sukces', 'Instrukcja resetowania hasła została wysłana na Twój e-mail');
        }
    }
}
