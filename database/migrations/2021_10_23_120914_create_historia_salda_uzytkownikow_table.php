<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHistoriaSaldaUzytkownikowTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('historia_salda_uzytkownikow', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('uzytkownik_id');
            $table->enum('rodzaj_salda', ['PLN', 'BTC']);   
            $table->enum('rodzaj_zmiany_salda', ['Dodanie', 'Odjęcie']);
            $table->enum('rodzaj_operacji', [
            'Wpłata PLN', 
            'Wypłata PLN',
            'Wpłata BTC',
            'Wypłata BTC',
            'Wystawienie oferty zakupu BTC',
            'Wystawienie oferty sprzedaży BTC',
            'Anulowanie oferty zakupu BTC',
            'Anulowanie oferty sprzedaży BTC',
            'Anulowanie wypłaty PLN',
            'Anulowanie wypłaty BTC',
            'Transakcja zakupu BTC',
            'Transakcja sprzedaży BTC',
            'Opłata abonamentowa']);
            $table->float('kwota_pln', 255, 2)->nullable();
            $table->float('kwota_btc', 255, 8)->nullable();
            $table->unsignedBigInteger('wplata_pln_id')->unique()->nullable();
            $table->unsignedBigInteger('wyplata_pln_id')->nullable();
            $table->unsignedBigInteger('wplata_btc_id')->unique()->nullable();
            $table->unsignedBigInteger('wyplata_btc_id')->nullable();
            $table->unsignedBigInteger('oferta_id')->nullable();
            $table->unsignedBigInteger('transakcja_id')->nullable();
            $table->float('saldo_pln_przed_rozpoczeciem_operacji', 255, 2)->nullable();
            $table->float('saldo_pln_po_zakonczeniu_operacji', 255, 2)->nullable();
            $table->float('saldo_btc_przed_rozpoczeciem_operacji', 255, 8)->nullable();
            $table->float('saldo_btc_po_zakonczeniu_operacji', 255, 8)->nullable();
            $table->timestamps();
            $table->foreign('uzytkownik_id')->references('id')->on('users')->onUpdate('cascade')
            ->onDelete('restrict');
            $table->foreign('wplata_pln_id')->references('id')->on('wplaty_pln')->onUpdate('cascade')
            ->onDelete('restrict');
            $table->foreign('wyplata_pln_id')->references('id')->on('wyplaty_pln')->onUpdate('cascade')
            ->onDelete('restrict');
            $table->foreign('wplata_btc_id')->references('id')->on('wplaty_btc')->onUpdate('cascade')
            ->onDelete('restrict');
            $table->foreign('wyplata_btc_id')->references('id')->on('wyplaty_btc')->onUpdate('cascade')
            ->onDelete('restrict');
            $table->foreign('oferta_id')->references('id')->on('oferty')->onUpdate('cascade')
            ->onDelete('restrict');
            $table->foreign('transakcja_id')->references('id')->on('transakcje')->onUpdate('cascade')
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
        Schema::dropIfExists('historia_salda_uzytkownikow');
    }
}
