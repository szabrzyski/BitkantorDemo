<?php

namespace App\Models;

use App\Libraries\PomocnikLiczbowy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class WyplataBtc extends Model
{
    use HasFactory;

    protected $guarded = ['*'];

    protected $table = 'wyplaty_btc';

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function transakcjaBlockchain()
    {
        return $this->belongsTo(TransakcjaBlockchain::class, 'transakcja_blockchain_id', 'id');
    }

    public function historiaSaldaBitkantor()
    {
        return $this->belongsTo(HistoriaSaldaBitkantor::class,'wyplata_btc_id','id');
    }

    public function historiaSaldaUzytkownika()
    {
        return $this->hasOne(HistoriaSaldaUzytkownikow::class, 'wyplata_btc_id', 'id');
    }
    
    public function uzytkownik()
    {
        return $this->belongsTo(User::class,'uzytkownik_id','id');
    }

    public function getKwotaBtcAttribute($kwotaBtc)
    {
        $pomocnikLiczbowy = new PomocnikLiczbowy();
        return $pomocnikLiczbowy->formatujLiczbe($kwotaBtc, 8);
    }

    public function getProwizjaBtcAttribute($prowizjaBtc)
    {
        $pomocnikLiczbowy = new PomocnikLiczbowy();
        return $pomocnikLiczbowy->formatujLiczbe($prowizjaBtc, 8);
    }

    public function dataAktualizacjiSformatowana()
    {
        return $this->created_at->format('d.m.Y H:i');
    }

}
