<?php

namespace Rumur\WordPress\Mailer;

class WordPressMailParams
{
    public $to;
    public $subject;
    public $body;
    public $headers;
    public $attachments;
    /** @var null|string */
    public $locale;

    /**
     * WordPressMailParams constructor.
     *
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param array $headers
     * @param array $attachments
     * @param null|string $locale
     */
    public function __construct(
        string $to, string $subject, string $body,
        array $headers = [], array $attachments = [],
        ?string $locale = null
    )
    {
        $this->to = $to;
        $this->subject = $subject;
        $this->body = $body;
        $this->headers = $headers;
        $this->attachments = $attachments;
        $this->locale = $locale;
    }
}