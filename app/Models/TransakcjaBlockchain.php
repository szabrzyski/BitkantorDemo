<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransakcjaBlockchain extends Model
{
    use HasFactory;

    protected $guarded = ['*'];

    protected $table = 'transakcje_blockchain';

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function wyplatyBtc()
    {
        return $this->hasMany(WyplataBtc::class, 'transakcja_blockchain_id', 'id');
    }

    public function wplatyBtc()
    {
        return $this->hasMany(WplataBtc::class, 'transakcja_blockchain_id', 'id');
    }

    public function historiaSaldaBitkantor()
    {
        return $this->hasOne(HistoriaSaldaBitkantor::class, 'transakcja_blockchain_id', 'id');
    }

}
