<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOfertyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('oferty', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wystawiajacy_id');
            $table->enum('typ', ['Zakup', 'Sprzedaż']);
            $table->float('kwota_pln',255,2);
            $table->float('kwota_btc',255,8);
            $table->float('pozostala_kwota_btc',255,8);
            $table->float('kurs',255,2);
            $table->enum('tryb_rozliczania', ['Prowizja', 'Abonament']);
            $table->float('prowizja_procent',5,2)->nullable();
            $table->float('prowizja_bitkantor_pln',255,2)->nullable();
            $table->float('prowizja_bitkantor_btc',255,8)->nullable();
            $table->enum('status', ['Aktywna', 'Anulowana', 'Zakończona']);
            $table->timestamps();
            $table->foreign('wystawiajacy_id')->references('id')->on('users')->onUpdate('cascade')
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
        Schema::dropIfExists('oferty');
    }
}
