<?php

namespace App\Models;

use App\Libraries\PomocnikLiczbowy;
use DateTimeInterface;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    // protected $fillable = [
    //     'email',
    //     'password',
    // ];

    protected $guarded = ['*'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        //  'email_verified_at' => 'datetime',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function czyJestAdminem() {
        return $this->jest_adminem == true;
    }

    //Relacje
    public function ofertyJakoWystawiajacy()
    {
        return $this->hasMany(Oferta::class, 'wystawiajacy_id', 'id');
    }

    public function transakcjeJakoWystawiajacy()
    {
        return $this->hasMany(Transakcja::class, 'wystawiajacy_id', 'id');
    }

    public function transakcjeJakoPrzyjmujacy()
    {
        return $this->hasMany(Transakcja::class, 'przyjmujacy_id', 'id');
    }

    public function transakcje()
    {
        return $this->transakcjeJakoWystawiajacy->merge($this->transakcjeJakoPrzyjmujacy);
    }

    public function wyplatyBtc()
    {
        return $this->hasMany(WyplataBtc::class, 'uzytkownik_id', 'id');
    }

    public function wplatyBtc()
    {
        return $this->hasMany(WplataBtc::class, 'uzytkownik_id', 'id');
    }

    public function wyplatyPln()
    {
        return $this->hasMany(WyplataPln::class, 'uzytkownik_id', 'id');
    }

    public function wplatyPln()
    {
        return $this->hasMany(WplataPln::class, 'uzytkownik_id', 'id');
    }

    public function historiaSalda()
    {
        return $this->hasOne(HistoriaSaldaUzytkownikow::class, 'uzytkownik_id', 'id');
    }

    public function prowizjaProcent()
    {
        if ($this->osobista_prowizja_procent) {
            return $this->osobista_prowizja_procent;
        } else {
            return config('app.prowizjaTransakcyjnaProcent');
        }
    }

    public function saldoBtcSformatowane()
    {
        $pomocnikLiczbowy = new PomocnikLiczbowy();
        return $pomocnikLiczbowy->formatujLiczbe($this->saldo_btc, 8);
    }

    public function saldoPlnSformatowane()
    {
        $pomocnikLiczbowy = new PomocnikLiczbowy();
        return $pomocnikLiczbowy->formatujLiczbe($this->saldo_pln, 2);
    }

}
