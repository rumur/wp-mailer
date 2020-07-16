<?php

namespace Rumur\WordPress\Mailer\Concerns;

use Rumur\WordPress\Mailer\Utils;
use Rumur\WordPress\Mailer\WordPressMailParams;

trait HasEmailAttributes
{
    /**
     * Who to send to.
     *
     * @var string
     */
    protected $to;

    /**
     * Who to send to as a carbon copy.
     *
     * @var string
     */
    protected $cc;

    /**
     * Who to send to as a blind carbon copy.
     *
     * @var string
     */
    protected $bcc;

    /**
     * Subject of the email.
     *
     * @var string
     */
    protected $subject;

    /**
     * Message of the email.
     *
     * @var string
     */
    protected $body;

    /**
     * The Locale of an email.
     *
     * @var string
     */
    protected $locale;

    /**
     * Headers that will be set to email.
     *
     * @var array
     */
    protected $headers = [];

    /**
     * The reply to address.
     *
     * @var string
     */
    protected $replyTo;

    /**
     * Sets a recipient.
     *
     * @param string|\WP_User $user.
     * @return static
     */
    public function to($user)
    {
        $this->to = $user;

        if (! $this->locale && $user instanceof \WP_User) {
            $this->locale($user->locale);
        }

        return $this;
    }

    /**
     * Sets a carbon copy recipient.
     *
     * @param string|string[]|\WP_User|\WP_User[] $cc Comma separated list of emails or an array of emails.
     * @return static
     */
    public function cc($cc)
    {
        $this->cc = $this->sanitizeEmail($cc);
        
        return $this;
    }

    /**
     * Sets a blind carbon copy recipient.
     *
     * @param string|string[]|\WP_User|\WP_User[] $bcc Comma separated list of emails or an array of emails.
     * @return static
     */
    public function bcc($bcc)
    {
        $this->bcc = $this->sanitizeEmail($bcc);

        return $this;
    }

    /**
     * Sets a subject of the email.
     *
     * @param string $subject
     * @return static
     */
    public function subject(string $subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Gets a subject of the email.
     *
     * @return string
     */
    public function getSubject(): string
    {
        if (!$this->subject) {
            $this->subject = Utils::classBasename(static::class);
        }

        return $this->subject;
    }

    /**
     * Sets a locale of the email.
     *
     * @param string $locale
     * @return static
     */
    public function locale(?string $locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * Sets headers.
     *
     * @param array $headers
     *
     * @return static
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Adds headers.
     *
     * @param $header
     * @return static
     */
    public function addHeaders($header)
    {
        $this->headers[] = $header;

        return $this;
    }

    /**
     * Gets the list of headers.
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param string|string[]|\WP_User|\WP_User[] $replyTo Comma separated list of emails or an array of emails.
     * @return static
     */
    public function replyTo($replyTo)
    {
        $this->replyTo = $this->sanitizeEmail($replyTo);

        return $this;
    }

    /**
     * @param string|string[]|\WP_User|\WP_User[] $emails Comma separated email or just an array of emails.
     *
     * @return string
     */
    public function sanitizeEmail($emails): string
    {
        return Utils::sanitizeEmails($emails);
    }

    /**
     * @return WordPressMailParams
     */
    public function build(): WordPressMailParams
    {
        $this->buildHeaders();

        return new WordPressMailParams(
            $this->sanitizeEmail($this->to),
            $this->getSubject(),
            $this->body(),
            $this->getHeaders(),
            $this->attachments(),
            $this->getLocale()
        );
    }

    protected function buildHeaders(): void
    {
        foreach ($this->headersToAttributesMap() as $header => $attribute) {
            if ($prop = $this->$attribute) {
                $this->addHeaders("{$header}: " . $prop);
            }
        }
    }

    protected function headersToAttributesMap(): array
    {
        return [
            'cc' => 'cc',
            'bcc' => 'bcc',
            'reply-to' => 'replyTo',
        ];
    }
}
