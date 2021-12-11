<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHistoriaSaldaBitkantor extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('historia_salda_bitkantor', function (Blueprint $table) {
            $table->id();
            $table->enum('rodzaj_salda', ['PLN', 'BTC']);   
            $table->enum('rodzaj_zmiany_salda', ['Dodanie', 'Odjęcie']);
            $table->enum('rodzaj_operacji', ['Prowizja transakcyjna', 'Prowizja za wpłatę PLN', 'Prowizja za wypłatę PLN', 'Prowizja za wypłatę BTC', 'Opłata za transakcję blockchain','Opłata za wypłatę PLN', 'Opłata abonamentowa', 'Własna']);
            $table->float('kwota_pln', 255, 2)->nullable();
            $table->float('kwota_btc', 255, 8)->nullable();
            $table->unsignedBigInteger('abonent_id')->nullable();
            $table->unsignedBigInteger('transakcja_id')->nullable();
            $table->unsignedBigInteger('wplata_pln_id')->unique()->nullable();
            $table->unsignedBigInteger('wyplata_pln_id')->nullable();
            $table->unsignedBigInteger('wplata_btc_id')->unique()->nullable();
            $table->unsignedBigInteger('wyplata_btc_id')->unique()->nullable();
            $table->unsignedBigInteger('transakcja_blockchain_id')->unique()->nullable();
            $table->float('saldo_pln_przed_rozpoczeciem_operacji', 255, 2);
            $table->float('saldo_pln_po_zakonczeniu_operacji', 255, 2);
            $table->float('saldo_btc_przed_rozpoczeciem_operacji', 255, 8);
            $table->float('saldo_btc_po_zakonczeniu_operacji', 255, 8);
            $table->timestamps();
            $table->unique(["rodzaj_salda", "transakcja_id"]);
            $table->foreign('abonent_id')->references('id')->on('users')->onUpdate('cascade')
            ->onDelete('restrict');
            $table->foreign('transakcja_id')->references('id')->on('transakcje')->onUpdate('cascade')
            ->onDelete('restrict');
            $table->foreign('wplata_pln_id')->references('id')->on('wplaty_pln')->onUpdate('cascade')
            ->onDelete('restrict');
            $table->foreign('wyplata_pln_id')->references('id')->on('wyplaty_pln')->onUpdate('cascade')
            ->onDelete('restrict');
            $table->foreign('wplata_btc_id')->references('id')->on('wplaty_btc')->onUpdate('cascade')
            ->onDelete('restrict');
            $table->foreign('wyplata_btc_id')->references('id')->on('wyplaty_btc')->onUpdate('cascade')
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
        Schema::dropIfExists('historia_salda_bitkantor');
    }
}
