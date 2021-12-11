<?php

namespace App\Models;

use App\Libraries\PomocnikLiczbowy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class Oferta extends Model
{
    use HasFactory;

    protected $guarded = ['*'];

    protected $table = 'oferty';

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function wystawiajacy()
    {
        return $this->belongsTo(User::class, 'wystawiajacy_id', 'id');
    }

    public function transakcjeJakoWystawiona()
    {
        return $this->hasMany(Transakcja::class, 'wystawiona_oferta_id', 'id');
    }

    public function transakcjeJakoPrzyjmujaca()
    {
        return $this->hasMany(Transakcja::class, 'przyjmujaca_oferta_id', 'id');
    }

    public function historiaSaldaUzytkownika()
    {
        return $this->hasOne(HistoriaSaldaUzytkownikow::class, 'oferta_id', 'id');
    }

    public function getPozostalaKwotaBtcAttribute($pozostalaKwotaBtc)
    {
        $pomocnikLiczbowy = new PomocnikLiczbowy();
        return $pomocnikLiczbowy->formatujLiczbe($pozostalaKwotaBtc, 8);
    }

    public function getKursAttribute($kurs)
    {
        $pomocnikLiczbowy = new PomocnikLiczbowy();
        return $pomocnikLiczbowy->formatujLiczbe($kurs, 2);
    }

}
