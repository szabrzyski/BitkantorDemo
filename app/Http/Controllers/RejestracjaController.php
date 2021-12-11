<?php

namespace App\Http\Controllers;

use App\Libraries\BitcoinApi;
use App\Mail\EmailWeryfikacyjny;
use App\Models\NiezarejestrowanyUzytkownik;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class RejestracjaController extends Controller
{

    public function zarejestrujUzytkownika(Request $request)
    {

        $request->validate([
            'email' => 'required|email|min:1|max:255|unique:users,email',
            'haslo' => 'required|min:8|max:255',
            // 'powtorzone_haslo' => 'same:haslo',
            'regulamin' => 'accepted',

        ],
            [
                // 'powtorzone_haslo.same' => 'Hasła nie zgadzają się.',
                'regulamin.accepted' => 'Regulamin musi zostać zaakceptowany',
                'email.unique' => 'Ten adres e-mail jest już w użyciu',
                'email.*' => 'Nieprawidłowy format adresu e-mail',
                'haslo.*' => 'Hasło musi mieć od 8 do 255 znaków',
            ]);

        $kodWeryfikacyjny = Hash::make($request->email) . Hash::make($request->haslo);

        $niezarejestrowanyUzytkownik = new NiezarejestrowanyUzytkownik;
        $niezarejestrowanyUzytkownik->email = $request->email;
        $niezarejestrowanyUzytkownik->password = Hash::make($request->haslo);
        $niezarejestrowanyUzytkownik->kod_weryfikacyjny = $kodWeryfikacyjny;
        $niezarejestrowanyUzytkownik->created_at = now();
        $zarejestrowanoUzytkownika = $niezarejestrowanyUzytkownik->save();

        if ($zarejestrowanoUzytkownika) {

            Mail::to($niezarejestrowanyUzytkownik)->send(new EmailWeryfikacyjny(Crypt::encryptString($kodWeryfikacyjny)));

            return redirect()->route('login')->with('sukces', 'Sprawdź swoją pocztę, aby aktywować konto');

        } else {
            return back()->with('blad', 'Wystąpił błąd podczas rejestrowania użytkownika');
        }
    }

    public function aktywujUzytkownika($kodWeryfikacyjny)
    {
        $kodWeryfikacyjny = Crypt::decryptString($kodWeryfikacyjny);

        $uzytkownikNiezweryfikowany = NiezarejestrowanyUzytkownik::where('kod_weryfikacyjny', $kodWeryfikacyjny)->first();

        if ($uzytkownikNiezweryfikowany !== null) {

            if (!User::where('email', $uzytkownikNiezweryfikowany->email)->exists()) {

                $uzytkownik = new User;

                $bitcoinApi = new BitcoinApi();
                $odpowiedzBitcoinApi = $bitcoinApi->wykonajZapytanie("getnewaddress", ["label" => "adresy_uzytkownikow"]);
                if ($odpowiedzBitcoinApi) {
                    $uzytkownik->adres_portfela = $odpowiedzBitcoinApi;
                } else {
                    return redirect()->route('zarejestruj')->with('blad', 'Wystąpił błąd podczas aktywowania konta');
                }

                $uzytkownik->email = $uzytkownikNiezweryfikowany->email;
                $uzytkownik->password = $uzytkownikNiezweryfikowany->password;
                $uzytkownik->tryb_rozliczania = "Prowizja";
                $uzytkownik->email_verified_at = now();
                $aktywowanoUzytkownika = $uzytkownik->save();
                if ($aktywowanoUzytkownika) {
                    $uzytkownikNiezweryfikowany->delete();
                    return redirect()->route('login')->with('sukces', 'Konto zostało aktywowane, możesz się zalogować');
                } else {
                    return redirect()->route('zarejestruj')->with('blad', 'Wystąpił błąd podczas aktywowania konta');
                }
            } else {
                return redirect()->route('login')->with('blad', 'Konto zostało już wcześniej aktywowane');
            }
        } else {
            return redirect()->route('login')->with('blad', 'Link aktywacyjny jest nieprawidłowy lub stracił ważność');
        }
    }
}
