<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransakcjeBlockchainTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transakcje_blockchain', function (Blueprint $table) {
            $table->id();
            $table->string("txid")->unique();
            $table->bigInteger("liczba_potwierdzen");
            $table->float('oplata_btc',255,8)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transakcje_blockchain');
    }
}
