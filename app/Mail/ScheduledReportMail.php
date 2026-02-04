<?php

namespace App\Mail;

use App\Models\ScheduledReport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ScheduledReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ScheduledReport $scheduledReport,
        public array $reports
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Scheduled Report: {$this->scheduledReport->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.scheduled-reports.report',
        );
    }

    public function attachments(): array
    {
        $attachments = [];

        foreach ($this->reports as $report) {
            $attachments[] = Attachment::fromData(
                fn() => $report['pdf_content'],
                $report['filename']
            )->withMime('application/pdf');
        }

        return $attachments;
    }
}
