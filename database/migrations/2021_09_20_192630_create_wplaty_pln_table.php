<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWplatyPlnTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wplaty_pln', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('uzytkownik_id');
            $table->string('tytul_przelewu')->nullable();
            $table->float('kwota_pln', 255, 2);
            $table->string('konto_bankowe_nadawcy');
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
        Schema::dropIfExists('wplaty_pln');
    }
}
