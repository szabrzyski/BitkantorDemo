<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class WplataBtc extends Model
{
    use HasFactory;

    protected $guarded = ['*'];

    protected $table = 'wplaty_btc';

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
        return $this->belongsTo(HistoriaSaldaBitkantor::class,'wplata_btc_id','id');
    }

    public function historiaSaldaUzytkownika()
    {
        return $this->hasOne(HistoriaSaldaUzytkownikow::class, 'wplata_btc_id', 'id');
    }

    public function uzytkownik()
    {
        return $this->belongsTo(User::class,'uzytkownik_id','id');
    }

}
