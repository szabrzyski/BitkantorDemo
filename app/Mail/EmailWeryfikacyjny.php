<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailWeryfikacyjny extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    public $kodWeryfikacyjny;

    public function __construct($kodWeryfikacyjny)
    {
        $this->kodWeryfikacyjny = $kodWeryfikacyjny;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        return $this
        ->subject('Aktywacja konta w Bitkantor.pl')
        ->markdown('emails.emailWeryfikacyjny', [
            'kodWeryfikacyjny' => $this->kodWeryfikacyjny,
        ]);

    }
}
