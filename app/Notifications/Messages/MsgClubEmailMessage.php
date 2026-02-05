<?php

namespace App\Notifications\Messages;

use Illuminate\Support\Facades\View;

class MsgClubEmailMessage
{
    public string $subject = '';
    public string $view = '';
    public array $viewData = [];
    public array $attachments = [];

    /**
     * Set email subject
     */
    public function subject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Set email view template
     */
    public function view(string $view, array $data = []): self
    {
        $this->view = $view;
        $this->viewData = $data;
        return $this;
    }

    /**
     * Add attachment to email
     */
    public function attach(string $path, ?string $name = null): self
    {
        if (!file_exists($path)) {
            throw new \InvalidArgumentException("Attachment file does not exist: {$path}");
        }

        $this->attachments[] = [
            'path' => $path,
            'name' => $name ?? basename($path),
        ];
        return $this;
    }

    /**
     * Render the email HTML
     */
    public function render(): string
    {
        if (empty($this->view)) {
            throw new \RuntimeException('Email view not set');
        }

        if (!View::exists($this->view)) {
            throw new \RuntimeException("Email view '{$this->view}' does not exist");
        }

        return view($this->view, $this->viewData)->render();
    }

    /**
     * Get all attachments
     */
    public function getAttachments(): array
    {
        return $this->attachments;
    }
}
