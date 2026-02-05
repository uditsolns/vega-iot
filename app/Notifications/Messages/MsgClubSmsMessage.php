<?php

namespace App\Notifications\Messages;

class MsgClubSmsMessage
{
    public string $content = '';
    public ?string $templateId = null;
    protected array $data = [];

    /**
     * Use a predefined template
     */
    public function template(string $key): self
    {
        $templates = config('notifications.templates.sms', []);

        if (!isset($templates[$key])) {
            throw new \InvalidArgumentException("SMS template '{$key}' not found in config");
        }

        $this->templateId = $templates[$key]['id'] ?? null;
        $this->content = $templates[$key]['content'] ?? '';

        return $this;
    }

    /**
     * Set template data for replacement
     */
    public function data(array $data): self
    {
        $this->data = $data;
        $this->replaceTemplateVariables();
        return $this;
    }

    /**
     * Set content directly (for custom messages)
     */
    public function content(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Set template ID
     */
    public function templateId(?string $templateId): self
    {
        $this->templateId = $templateId;
        return $this;
    }

    /**
     * Replace template variables with actual data
     */
    protected function replaceTemplateVariables(): void
    {
        foreach ($this->data as $key => $value) {
            $this->content = str_replace("{{$key}}", (string)$value, $this->content);
        }
    }
}
