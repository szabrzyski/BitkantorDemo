<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class HistoriaSaldaBitkantor extends Model
{
    use HasFactory;

    protected $guarded = ['*'];

    protected $table = 'historia_salda_bitkantor';

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function abonent()
    {
        return $this->belongsTo(User::class, 'abonent_id', 'id');
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

    public function transakcjaBlockchain()
    {
        return $this->belongsTo(TransakcjaBlockchain::class, 'transakcja_blockchain_id', 'id');
    }

}
