<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSaldoBitkantorTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('saldo_bitkantor', function (Blueprint $table) {
            $table->id();
            $table->float('saldo_pln',255,2)->default(0.00);
            $table->float('saldo_btc',255,8)->default(0.00000000);
            $table->timestamps();
        });
        
        $obecnyCzas = now();

        DB::table('saldo_bitkantor')->insert([
            'created_at' => $obecnyCzas,
            'updated_at' => $obecnyCzas
        ]);

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('saldo_bitkantor');
    }
}
