<?php

namespace App\Mail;

use App\Models\Website;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WebsiteOfflineNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Website $website,
        public ?int $statusCode,
        public ?string $errorMessage,
    ) {
    }

    public function build()
    {
        return $this->subject("Website offline: {$this->website->name}")
            ->view('emails.website-offline')
            ->with([
                'website' => $this->website,
                'statusCode' => $this->statusCode,
                'errorMessage' => $this->errorMessage,
            ]);
    }
}
