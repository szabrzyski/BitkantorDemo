<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class HistoriaSaldaUzytkownikow extends Model
{
    use HasFactory;

    protected $guarded = ['*'];

    protected $table = 'historia_salda_uzytkownikow';

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function uzytkownik()
    {
        return $this->belongsTo(User::class, 'uzytkownik_id', 'id');
    }

    public function oferta()
    {
        return $this->belongsTo(Oferta::class, 'oferta_id', 'id');
    }

    public function transakcja()
    {
        return $this->belongsTo(Transakcja::class, 'transakcja_id', 'id');
    }

    public function wplataPln()
    {
        return $this->belongsTo(WplataPln::class, 'wplata_pln_id', 'id');
    }

    public function wyplataPln()
    {
        return $this->belongsTo(WyplataPln::class, 'wyplata_pln_id', 'id');
    }

    public function wplataBtc()
    {
        return $this->belongsTo(WplataBtc::class, 'wplata_btc_id', 'id');
    }

    public function wyplataBtc()
    {
        return $this->belongsTo(WyplataBtc::class, 'wyplata_btc_id', 'id');
    }

}
