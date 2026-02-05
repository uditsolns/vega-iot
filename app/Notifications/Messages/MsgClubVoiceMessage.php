<?php

namespace App\Notifications\Messages;

class MsgClubVoiceMessage
{
    public string $content = '';
    protected array $data = [];

    /**
     * Set voice message content
     */
    public function content(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Use a predefined template
     */
    public function template(string $key): self
    {
        $templates = config('notifications.templates.voice', []);

        if (!isset($templates[$key])) {
            $this->content = "Alert notification from VEGA IoT System";
        } else {
            $this->content = $templates[$key];
        }

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
     * Replace template variables
     */
    protected function replaceTemplateVariables(): void
    {
        foreach ($this->data as $key => $value) {
            $this->content = str_replace("{{$key}}", (string)$value, $this->content);
        }
    }
}
