<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class WplataPln extends Model
{
    use HasFactory;

    protected $guarded = ['*'];

    protected $table = 'wplaty_pln';

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function historiaSaldaBitkantor()
    {
        return $this->belongsTo(HistoriaSaldaBitkantor::class,'wplata_pln_id','id');
    }

    public function historiaSaldaUzytkownika()
    {
        return $this->hasOne(HistoriaSaldaUzytkownikow::class, 'wplata_pln_id', 'id');
    }
    
    public function uzytkownik()
    {
        return $this->belongsTo(User::class,'uzytkownik_id','id');
    }

}
