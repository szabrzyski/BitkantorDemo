<?php

namespace App\Http\Controllers;

use App\Models\ResetowanieHasla;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class ResetowanieHaslaFinalizacjaController extends Controller
{

    public function zresetujHaslo(Request $request)
    {

        $request->validate([
            'haslo' => 'required|string|min:8|max:255',
            'powtorzoneHaslo' => 'same:haslo',
        ],
            [
                'haslo.*' => 'Hasło musi mieć od 8 do 255 znaków',
                'powtorzoneHaslo.same' => 'Hasła nie zgadzają się',
            ]);

        if ($request->has('kodWeryfikacyjny')) {
            $kodWeryfikacyjny = Crypt::decryptString($request->kodWeryfikacyjny);
            if (ResetowanieHasla::where('kod_weryfikacyjny', $kodWeryfikacyjny)->exists()) {

                $resetHasla = ResetowanieHasla::where('kod_weryfikacyjny', $kodWeryfikacyjny)->first();
                $roznicaGodzin = now()->diffInHours($resetHasla->created_at);
                if ($roznicaGodzin <= 24) {
                    if (User::where('email', $resetHasla->email)->exists()) {

                        $uzytkownik = User::where('email', $resetHasla->email)->first();
                        $uzytkownik->password = Hash::make($request->haslo);
                        $zmienionoHasloUzytkownikowi = $uzytkownik->save();
                        if ($zmienionoHasloUzytkownikowi) {
                            $resetHasla->delete();
                            return redirect()->route('login')->with('sukces', 'Twoje hasło zostało zmienione');
                        } else {
                            return redirect()->route('resetHasla')->with('blad', 'Wystąpił błąd podczas zmiany hasła');
                        }

                    } else {
                        // Nie ma takiego użytkownika, ale i tak podajemy fałszywy komunikat
                        return redirect()->route('resetHasla')->with('blad', 'Link resetujący jest nieprawidłowy lub stracił ważność');
                    }
                } else {
                    // Kod weryfikacyjny stracił ważność
                    $resetHasla->delete();
                    return redirect()->route('resetHasla')->with('blad', 'Link resetujący jest nieprawidłowy lub stracił ważność');
                }

            } else {
                // Nie ma takiego kodu weryfikacyjnego
                return redirect()->route('resetHasla')->with('blad', 'Link resetujący jest nieprawidłowy lub stracił ważność');
            }
        } else {
            // Nie ma kodu weryfikacyjnego w żądaniu
            return redirect()->route('resetHasla')->with('blad', 'Link resetujący jest nieprawidłowy lub stracił ważność');
        }
    }
}
