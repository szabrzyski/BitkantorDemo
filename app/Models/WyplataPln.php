<?php

namespace App\Models;

use App\Libraries\PomocnikLiczbowy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class WyplataPln extends Model
{
    use HasFactory;

    protected $guarded = ['*'];

    protected $table = 'wyplaty_pln';

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function historiaSaldaBitkantor()
    {
        return $this->belongsTo(HistoriaSaldaBitkantor::class,'wyplata_pln_id','id');
    }

    public function historiaSaldaUzytkownika()
    {
        return $this->hasOne(HistoriaSaldaUzytkownikow::class, 'wyplata_pln_id', 'id');
    }
    
    public function uzytkownik()
    {
        return $this->belongsTo(User::class,'uzytkownik_id','id');
    }

    public function getKwotaPlnAttribute($kwotaPln)
    {
        $pomocnikLiczbowy = new PomocnikLiczbowy();
        return $pomocnikLiczbowy->formatujLiczbe($kwotaPln, 2);
    }

    public function getProwizjaPlnAttribute($prowizjaPln)
    {
        $pomocnikLiczbowy = new PomocnikLiczbowy();
        return $pomocnikLiczbowy->formatujLiczbe($prowizjaPln, 2);
    }

    public function dataAktualizacjiSformatowana()
    {
        return $this->created_at->format('d.m.Y H:i');
    }

}
