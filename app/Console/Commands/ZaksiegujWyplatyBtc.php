<?php

namespace App\Console\Commands;

use App\Http\Controllers\WyplataBtcController;
use Illuminate\Console\Command;

class ZaksiegujWyplatyBtc extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wyplatyBtc:zaksiegujWyplatyBtc';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Księgowanie wypłat BTC';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $kontrolerWyplatyBtc = new WyplataBtcController();
        $wynikZaksiegowaniaWyplatBtc = $kontrolerWyplatyBtc->zaksiegujWyplatyBtc();
        if ($wynikZaksiegowaniaWyplatBtc) {
            $this->info("Księgowanie wypłat BTC zakończone pomyślnie");
        } else {
            $this->info("Błąd podczas księgowania wypłat BTC");
        }
    }
}
