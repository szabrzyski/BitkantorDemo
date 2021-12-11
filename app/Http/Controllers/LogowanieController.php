<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogowanieController extends Controller
{

    public function zalogujUzytkownika(Request $request)
    {

        $request->validate([
            'email' => 'required|email|min:1|max:255',
            'haslo' => 'required|string|min:8|max:255',
        ],
            [
                'email.*' => 'Nieprawidłowy format adresu e-mail',
                'haslo.*' => 'Hasło musi mieć od 8 do 255 znaków',
            ]);

        $email = $request->email;
        $haslo = $request->haslo;

        if (Auth::guard('web')->attempt(['email' => $email, 'password' => $haslo], true)) {
            $request->session()->regenerate();

            return redirect()->intended('/');
        }

        return back()->with([
            'blad' => 'Nieprawidłowe dane logowania',
        ]);
    }

    public function wylogujUzytkownika(Request $request)
    {

        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('glowna');

    }
}
