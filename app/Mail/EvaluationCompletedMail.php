<?php

namespace App\Mail;

use App\Models\Candidate;
use App\Models\Evaluation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EvaluationCompletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Candidate $candidate,
        public Evaluation $evaluation
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Evaluation Complete: {$this->candidate->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.evaluation-completed',
        );
    }
}
