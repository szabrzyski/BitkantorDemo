<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWyplatyPlnTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wyplaty_pln', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('uzytkownik_id');
            $table->float('kwota_pln',255,2);
            $table->float('prowizja_pln',255,2);
            $table->float('oplata_pln',255,2)->nullable();
            $table->string('tytul_przelewu')->nullable();
            $table->string('konto_bankowe_odbiorcy');
            $table->enum('status', ['Zlecona', 'Anulowana', 'Realizowana', 'Zakończona']);
            $table->timestamps();
            $table->foreign('uzytkownik_id')->references('id')->on('users')->onUpdate('cascade')
            ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wyplaty_pln');
    }
}
