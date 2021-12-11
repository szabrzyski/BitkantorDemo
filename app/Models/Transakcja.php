<?php

namespace App\Models;

use App\Libraries\PomocnikLiczbowy;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transakcja extends Model
{
    use HasFactory;

    protected $guarded = ['*'];

    protected $table = 'transakcje';

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function wystawionaOferta()
    {
        return $this->belongsTo(Oferta::class, 'wystawiona_oferta_id', 'id');
    }

    public function przyjmujacaOferta()
    {
        return $this->belongsTo(Oferta::class, 'przyjmujaca_oferta_id', 'id');
    }

    public function wystawiajacy()
    {
        return $this->belongsTo(User::class, 'wystawiajacy_id', 'id');
    }

    public function przyjmujacy()
    {
        return $this->belongsTo(User::class, 'przyjmujacy_id', 'id');
    }

    public function historiaSaldaBitkantor()
    {
        return $this->hasOne(HistoriaSaldaBitkantor::class, 'transakcja_id', 'id');
    }

    public function historiaSaldaUzytkownika()
    {
        return $this->hasOne(HistoriaSaldaUzytkownikow::class, 'transakcja_id', 'id');
    }

    public function getKursAttribute($kurs)
    {
        $pomocnikLiczbowy = new PomocnikLiczbowy();
        return $pomocnikLiczbowy->formatujLiczbe($kurs, 2);
    }

    public function getKwotaPlnAttribute($kwotaPln)
    {
        $pomocnikLiczbowy = new PomocnikLiczbowy();
        return $pomocnikLiczbowy->formatujLiczbe($kwotaPln, 2);
    }

    public function getKwotaBtcAttribute($kwotaBtc)
    {
        $pomocnikLiczbowy = new PomocnikLiczbowy();
        return $pomocnikLiczbowy->formatujLiczbe($kwotaBtc, 8);
    }

}
