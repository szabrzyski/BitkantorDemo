<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->string('imie')->nullable();
            $table->string('nazwisko')->nullable();
            $table->string('adres')->nullable();
            $table->string('miasto')->nullable();
            $table->string('kod_pocztowy')->nullable();
            $table->string('adres_portfela')->nullable()->unique();
            $table->boolean('zweryfikowany')->default(0);
            $table->boolean('zablokowany')->default(0);
            $table->float('saldo_btc',255,8)->default(0.00000000);
            $table->float('saldo_pln',255,2)->default(0.00);
            $table->boolean('jest_adminem')->default(0);
            $table->enum('tryb_rozliczania', ['Prowizja', 'Abonament']);
            $table->float('osobista_prowizja_procent',5,2)->nullable();
            $table->timestamp('koniec_abonamentu')->nullable();
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
        Schema::dropIfExists('users');
    }
}
