<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWyplatyBtcTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wyplaty_btc', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('uzytkownik_id');
            $table->unsignedBigInteger('transakcja_blockchain_id')->nullable();
            $table->float('kwota_btc',255,8);
            $table->float('prowizja_btc',255,8);
            $table->string('adres_portfela_do_wyplaty');
            $table->enum('status', ['Zlecona', 'Anulowana', 'Realizowana', 'Wysłana', 'Zakończona']);
            $table->timestamps();
            $table->unique(["transakcja_blockchain_id", "adres_portfela_do_wyplaty"], 'tx_id_adres_portfela_odbiorcy_unikalne');
            $table->foreign('uzytkownik_id')->references('id')->on('users')->onUpdate('cascade')
            ->onDelete('restrict');
            $table->foreign('transakcja_blockchain_id')->references('id')->on('transakcje_blockchain')->onUpdate('cascade')
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
        Schema::dropIfExists('wyplaty_btc');
    }
}
