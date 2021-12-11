<?php

use App\Http\Controllers\Admin\AdminIndexController;
use App\Http\Controllers\Admin\AdminWyplataBtcController;
use App\Http\Controllers\Admin\AdminWyplataPlnController;
use App\Http\Controllers\HistoriaTransakcjiController;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\LogowanieController;
use App\Http\Controllers\OfertaController;
use App\Http\Controllers\RejestracjaController;
use App\Http\Controllers\ResetowanieHaslaController;
use App\Http\Controllers\ResetowanieHaslaFinalizacjaController;
use App\Http\Controllers\SaldoBtcController;
use App\Http\Controllers\SaldoPlnController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\WyplataBtcController;
use App\Http\Controllers\WyplataPlnController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
 */

// Limit ilości zapytań
Route::middleware(['throttle:limitGlobalny'])->group(function () {

    // Strona główna
    Route::get('/', [IndexController::class, 'index'])->name('glowna');

    // Logowanie
    Route::get('/login', function () {
        return view('logowanie');
    })->name('login')->middleware('guest');

    Route::post('/login', [LogowanieController::class, 'zalogujUzytkownika'])->name('zaloguj')->middleware('guest');

    // Wylogowanie
    Route::get('/wylogowanie', [LogowanieController::class, 'wylogujUzytkownika'])->name('wyloguj')->middleware('auth');

    // Rejestracja
    Route::get('/rejestracja', function () {
        return view('rejestracja');
    })->name('rejestracja')->middleware('guest');

    Route::post('/rejestracja', [RejestracjaController::class, 'zarejestrujUzytkownika'])->name('zarejestruj')->middleware('guest');

    Route::get('/rejestracja/{kodWeryfikacyjny}', [RejestracjaController::class, 'aktywujUzytkownika'])->name('aktywuj')->middleware('guest');

    // Resetowanie hasła
    Route::get('/resetowanieHasla', function () {
        return view('resetowanieHasla');
    })->name('resetHasla')->middleware('guest');

    Route::post('/resetowanieHasla', [ResetowanieHaslaController::class, 'wyslijLinkResetujacy'])->name('wyslijLinkResetujacy')->middleware('guest');

    Route::get('/resetowanieHasla/{kodWeryfikacyjny}', function ($kodWeryfikacyjny) {
        return view('resetowanieHaslaFinalizacja')->with('kodWeryfikacyjny', $kodWeryfikacyjny);
    })->name('dokonczResetHasla')->middleware('guest');

    Route::post('/resetHaslaFinalizacja', [ResetowanieHaslaFinalizacjaController::class, 'zresetujHaslo'])->name('zresetujHaslo')->middleware('guest');

    // Historia transakcji
    Route::get('/historiaTransakcji', [HistoriaTransakcjiController::class, 'historiaTransakcji'])->name('historiaTransakcji')->middleware('auth');

    // Wpłaty i wypłaty BTC oraz ich historia
    Route::get('/saldoBtc', [SaldoBtcController::class, 'saldoBtc'])->name('saldoBtc')->middleware('auth');

    // Wpłaty i wypłaty PLN oraz ich historia
    Route::get('/saldoPln', [SaldoPlnController::class, 'saldoPln'])->name('saldoPln')->middleware('auth');

    // Wypłata BTC
    Route::post('/saldoBtc/wyplacBtc', [WyplataBtcController::class, 'wyplacBtc'])->name('wyplacBtc')->can('wyplacBtc', 'App\Models\WyplataBtc');

    // Anulowanie wypłaty BTC
    Route::post('/saldoBtc/anulujWyplate/{wyplataBtc}', [WyplataBtcController::class, 'anulujWyplateBtc'])->name('anulujWyplateBtc')->can('anulujWyplateBtc', 'wyplataBtc');

    // Wypłata PLN
    Route::post('/saldoBtc/wyplacPln', [WyplataPlnController::class, 'wyplacPln'])->name('wyplacPln')->can('wyplacPln', 'App\Models\WyplataPln');

    // Anulowanie wypłaty PLN
    Route::post('/saldoPln/anulujWyplate/{wyplataPln}', [WyplataPlnController::class, 'anulujWyplatePln'])->name('anulujWyplatePln')->can('anulujWyplatePln', 'wyplataPln');

    // --- Administracja ---

    // Panel admina
    Route::get('/admin', [AdminIndexController::class, 'index'])->name('panelAdmina')->middleware('uzytkownikJestAdminem');

    // Panel admina - Wypłaty BTC
    Route::get('/admin/wyplatyBtc', [AdminWyplataBtcController::class, 'wyplatyBtc'])->name('wyplatyBtc')->middleware('uzytkownikJestAdminem');

    // Panel admina - Anulowanie wypłaty BTC
    Route::post('/admin/wyplatyBtc/anulujWyplate/{wyplataBtc}', [AdminWyplataBtcController::class, 'anulujWyplateBtc'])->name('adminAnulujWyplateBtc')->middleware('uzytkownikJestAdminem');

    // Panel admina - Zablokowanie wypłaty BTC
    Route::post('/admin/wyplatyBtc/zablokujWyplate/{wyplataBtc}', [AdminWyplataBtcController::class, 'zablokujWyplateBtc'])->name('adminZablokujWyplateBtc')->middleware('uzytkownikJestAdminem');

    // Panel admina - Realizacja wypłaty BTC
    Route::post('/admin/wyplatyBtc/zrealizujWyplate/{wyplataBtc}', [AdminWyplataBtcController::class, 'zrealizujWyplateBtc'])->name('adminZrealizujWyplateBtc')->middleware('uzytkownikJestAdminem');

    // Panel admina - Wypłaty PLN
    Route::get('/admin/wyplatyPln', [AdminWyplataPlnController::class, 'wyplatyPln'])->name('wyplatyPln')->middleware('uzytkownikJestAdminem');

    // Panel admina - Anulowanie wypłaty PLN
    Route::post('/admin/wyplatyPln/anulujWyplate/{wyplataPln}', [AdminWyplataPlnController::class, 'anulujWyplatePln'])->name('adminAnulujWyplatePln')->middleware('uzytkownikJestAdminem');

    // Panel admina - Zablokowanie wypłaty PLN
    Route::post('/admin/wyplatyPln/zablokujWyplate/{wyplataPln}', [AdminWyplataPlnController::class, 'zablokujWyplatePln'])->name('adminZablokujWyplatePln')->middleware('uzytkownikJestAdminem');

    // Panel admina - Realizacja wypłaty PLN
    Route::post('/admin/wyplatyPln/zrealizujWyplate/{wyplataPln}', [AdminWyplataPlnController::class, 'zrealizujWyplatePln'])->name('adminZrealizujWyplatePln')->middleware('uzytkownikJestAdminem');

    // Funkcja testowa
    Route::get('/test', [TestController::class, 'test'])->name('test')->middleware('uzytkownikJestAdminem');

    // Funkcja testowa 2
    Route::get('/test2', [TestController::class, 'test2'])->name('test2')->middleware('uzytkownikJestAdminem');

    // Funkcja testowa Axios
    Route::get('/testAxios', [TestController::class, 'testAxios'])->name('testAxios')->middleware('uzytkownikJestAdminem');

    // --- Obsługiwane przez Axios ---

    // Wystawienie oferty
    Route::post('/oferty/wystaw', [OfertaController::class, 'wystawOferte'])->name('wystawOferte')->can('wystawOferte', 'App\Models\Oferta');

    // Anulowanie oferty
    Route::post('/oferty/anuluj/{oferta}', [OfertaController::class, 'anulujOferte'])->name('anulujOferte')->can('anulujOferte', 'oferta');

    // Ostatnie transakcje
    Route::get('/pobierzOstatnieTransakcje', [IndexController::class, 'pobierzOstatnieTransakcje'])->name('pobierzOstatnieTransakcje');

    // Aktywne oferty
    Route::get('/pobierzOferty', [IndexController::class, 'pobierzOferty'])->name('pobierzOferty');

    // Statystyki (kursy i wolumen z 24h)
    Route::get('/pobierzStatystyki', [IndexController::class, 'pobierzStatystyki'])->name('pobierzStatystyki');

    // Saldo i prowizja użytkownika
    Route::get('/pobierzSaldoOrazProwizje', [IndexController::class, 'pobierzSaldoOrazProwizje'])->name('pobierzSaldoOrazProwizje');

});

// });
