<?php

namespace App\Console\Commands;

use App\Http\Controllers\WplataBtcController;
use Illuminate\Console\Command;

class WykryjOrazZaksiegujWplatyBtc extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wplatyBtc:wykryjOrazZaksiegujWplatyBtc';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Wykrywanie oraz księgowanie wpłat BTC';

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
        $kontrolerWplatyBtc = new WplataBtcController();
        $wynikWykryciaWplatBtc = $kontrolerWplatyBtc->wykryjWplatyBtc();
        if ($wynikWykryciaWplatBtc) {
            $this->info("Wykrywanie wpłat BTC zakończone pomyślnie");
        } else {
            $this->info("Błąd podczas wykrywania wpłat BTC");
        }
        $wynikZaksiegowaniaWplatBtc = $kontrolerWplatyBtc->zaksiegujWplatyBtc();
        if ($wynikZaksiegowaniaWplatBtc) {
            $this->info("Księgowanie wpłat BTC zakończone pomyślnie");
        } else {
            $this->info("Błąd podczas księgowania wpłat BTC");
        }
    }
}
