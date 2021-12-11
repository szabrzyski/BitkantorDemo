<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function __construct()
    {
        $this->authorizeResource(User::class, 'user');
    }

    public function index()
    {
        $results = User::latest()->paginate(15)->appends(request()->except('page'));
        return view('users.index', compact('results'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('users.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|max:100',

        ],
            [
                'email.unique' => 'E-mail istnieje już w naszej bazie.',
            ]);

        $nowyUzytkownik = new User;
        $nowyUzytkownik->email = $request->email;
        $nowyUzytkownik->email_verified_at = now();
        $nowyUzytkownik->password = Hash::make($request->password);
        $zapisanoUzytkownika = $nowyUzytkownik->save();

        if ($zapisanoUzytkownika) {
            return redirect()->route('users.index')->with('sukces', 'Użytkownik został dodany');

        } else {
            return back()->with('blad', 'Wystąpił błąd podczas dodawania użytkownika')->withInput();
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        $uzytkownik = User::where('id', $user->id)->first();
        return view('users.show')->with('user', $uzytkownik);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\User $user
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {

        $uzytkownik = User::where('id', $user->id)->first();
        return view('users.edit')->with('user', $uzytkownik);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Models\User $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {

        $request->validate([
            'email' => 'required|email',
            'password' => 'nullable|min:8|max:100',

        ],
            [
            ]);

        $aktualizowanyUzytkownik = User::where('id', $user->id)->first();

        if (!User::where('email', $aktualizowanyUzytkownik->email)->where('id', '!=', $aktualizowanyUzytkownik->id)->exists()) {

            $aktualizowanyUzytkownik->email = $request->email;
            if ($request->password !== null) {
                $aktualizowanyUzytkownik->password = Hash::make($request->password);
            }
            $zaktualizowanoUzytkownika = $aktualizowanyUzytkownik->save();

            if ($zaktualizowanoUzytkownika) {
                return redirect()->route('users.index')->with('sukces', 'Dane użytkownika zostały zaktualizowane');

            } else {
                return back()->with('blad', 'Wystąpił błąd podczas aktualizacji użytkownika');
            }

        } else {
            return back()->with('blad', 'E-mail istnieje już w naszej bazie');
        }

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        if ($user->oferty()->doesntExist()) {
            if ($user->delete()) {
                return back()->with('sukces', 'Użytkownik został usunięty');
            } else {
                return back()->with('blad', 'Wystąpił błąd podczas usuwania użytkownika');
            }
        } else {
            return back()->with('blad', 'Użytkownik ma wystawione oferty');
        }

    }
}
