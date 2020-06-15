<?php

namespace Rumur\WordPress\Mailer;

abstract class Mailable
{
    use Traits\HasAttachments,
        Traits\HasEmailListeners,
        Traits\HasEmailAttributes;

    /**
     * Builds the body for a delivering.
     *
     * @return string
     */
    abstract public function body(): string;

    /**
     * Renders the Email body.
     *
     * @return string
     */
    public function render(): string
    {
        if ($this->locale && function_exists('switch_to_locale')) {
            \switch_to_locale($this->locale);
        }

        $body = $this->body();

        if ($this->locale && function_exists('is_locale_switched') && is_locale_switched()) {
            \restore_previous_locale();
        }

        return $body;
    }

    /**
     * Send an instance.
     *
     * @return bool
     */
    public function send(): bool
    {
        return Mailer::instance()->send($this);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->render();
    }
}