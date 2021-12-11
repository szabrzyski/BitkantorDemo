<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransakcjeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transakcje', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wystawiona_oferta_id');
            $table->unsignedBigInteger('przyjmujaca_oferta_id');
            $table->unsignedBigInteger('wystawiajacy_id');
            $table->unsignedBigInteger('przyjmujacy_id');
            $table->enum('typ_oferty_wystawianej', ['Zakup', 'Sprzedaż']);
            $table->enum('typ_oferty_przyjmujacej', ['Zakup', 'Sprzedaż']);
            $table->float('kwota_btc', 255, 8);
            $table->float('kwota_pln', 255, 2);
            $table->float('kurs', 255, 2);
            $table->enum('tryb_rozliczania_wystawiajacego', ['Prowizja', 'Abonament']);
            $table->enum('tryb_rozliczania_przyjmujacego', ['Prowizja', 'Abonament']);
            $table->float('prowizja_wystawiajacego_procent', 5, 2)->nullable();
            $table->float('prowizja_przyjmujacego_procent', 5, 2)->nullable();
            $table->float('prowizja_wystawiajacego_pln', 255, 2)->nullable();
            $table->float('prowizja_wystawiajacego_btc', 255, 8)->nullable();
            $table->float('prowizja_przyjmujacego_pln', 255, 2)->nullable();
            $table->float('prowizja_przyjmujacego_btc', 255, 8)->nullable();
            $table->float('prowizja_bitkantor_pln', 255, 2)->nullable();
            $table->float('prowizja_bitkantor_btc', 255, 8)->nullable();
            $table->timestamps();
            
            $table->foreign('wystawiona_oferta_id')->references('id')->on('oferty')->onUpdate('cascade')
                ->onDelete('restrict');
            $table->foreign('przyjmujaca_oferta_id')->references('id')->on('oferty')->onUpdate('cascade')
                ->onDelete('restrict');
            $table->foreign('wystawiajacy_id')->references('id')->on('users')->onUpdate('cascade')
                ->onDelete('restrict');
            $table->foreign('przyjmujacy_id')->references('id')->on('users')->onUpdate('cascade')
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
        Schema::dropIfExists('transakcje');
    }
}
