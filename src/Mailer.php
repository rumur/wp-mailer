<?php

namespace Rumur\WordPress\Mailer;

class Mailer
{
    /**
     * @var Mailer
     */
    protected static $instance;

    /**
     * The `FromEmail` that will be added during all send process.
     *
     * @var string
     */
    protected static $alwaysFromEmail;

    /**
     * The `FromName` that will be added during all send process.
     *
     * @var string
     */
    protected static $alwaysFromName;

    /** @var string */
    protected $fromEmail;

    /** @var string */
    protected $fromName;

    /**
     * The Charset of the sending email.
     *
     * @var string
     */
    protected $charset = 'UTF-8';

    /**
     * The original Charset of the mailer.
     *
     * @var string
     */
    protected $origCharset;

    /**
     * Mailer constructor.
     */
    protected function __construct()
    {
    }

    /**
     * Gets the instance of the mailer.
     *
     * @return Mailer
     */
    public static function instance(): Mailer
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * @param string|string[]|\WP_User|\WP_User[] $email
     * @param string|null $fromName
     * @param string|null $fromEmail
     * @return Compose
     */
    public static function make($email, ?string $fromName = null, ?string $fromEmail = null): Compose
    {
        $instance = static::instance();

        if ($fromName) {
            $instance->from($fromName, $fromEmail);
        }

        return $instance->to($email);
    }

    /**
     * Sets the From attributes.
     *
     * @param string $fromName
     * @param null $fromEmail
     *
     * @return Mailer
     */
    public function from(string $fromName, $fromEmail = null): Mailer
    {
        $this->fromName = $fromName;
        $this->fromEmail = $fromEmail;

        return $this;
    }

    /**
     * Gets the `$fromName` for the email.
     *
     * @return string|null
     */
    public function fromName(): ?string
    {
        return $this->fromName ?? static::$alwaysFromName;
    }

    /**
     * Gets the `$fromEmail` for the email.
     *
     * @return string|null
     */
    public function fromEmail(): ?string
    {
        return $this->fromEmail ?? static::$alwaysFromEmail;
    }

    /**
     * Flushes the context bound params.
     */
    protected function flushEmailParams(): void
    {
        $this->fromName = $this->fromEmail = null;

        if ($this->origCharset) {
            $this->charset = $this->origCharset;
        }
    }

    /**
     * @param string $charset
     *
     * @return static
     */
    public function useCharset(string $charset)
    {
        if (!$this->origCharset) {
            $this->origCharset = $this->charset;
        }

        $this->charset = $charset;

        return $this;
    }

    /**
     * Set the "From name", that would apply for all emails.
     *
     * @note be aware that the `$fromName` has a higher priority.
     *
     * @param string $name
     */
    public static function useAlwaysFromName(string $name): void
    {
        static::$alwaysFromName = $name;
    }

    /**
     * Set the "From email", that would apply for all emails.
     *
     * @note be aware that the `$fromEmail` has a higher priority.
     *
     * @param string $email
     */
    public static function useAlwaysFromEmail(string $email): void
    {
        static::$alwaysFromEmail = $email;
    }

    /**
     * Start with composing an email.
     *
     * @param  string|string[]|\WP_User|\WP_User[] $email
     *
     * @return Compose
     */
    public function to($email): Compose
    {
        return (new Compose($this))->to($email);
    }

    /**
     * Send an Mailable instance.
     *
     * @param Mailable $mailable
     * @return bool
     */
    public function send(Mailable $mailable): bool
    {
        $dispatched = (new Dispatcher())
            ->withCharset(
                $this->charset
            )->withFrom([
                'name' => $this->fromName(),
                'email' => $this->fromEmail(),
            ])->onSuccess(
                $mailable->successListeners()
            )->onFailure(
                $mailable->failedListeners()
            )->dispatch(
                $mailable->build()
            );

        $this->flushEmailParams();

        return $dispatched;
    }

    /**
     * Sends an raw email.
     *
     * @param string|\WP_User $to
     * @param string $subject
     * @param string $body
     * @param array $headers
     * @param array $attachments
     * @param null|\Closure $fn
     *
     * @return bool
     */
    public function raw(
        $to,
        string $subject,
        string $body,
        array $attachments = [],
        array $headers = [],
        ?\Closure $fn = null
    ): bool {
        $locale = null;

        if ($to instanceof \WP_User) {
            $locale = $to->locale;
        }

        $to = Utils::sanitizeEmails($to);

        $dispatcher = (new Dispatcher())->withCharset(
            $this->charset
        )->withFrom([
            'name' => $this->fromName(),
            'email' => $this->fromEmail(),
        ]);

        if ($fn) {
            $dispatcher->onSuccess($fn);
        }

        $dispatched = $dispatcher->dispatch(
            new WordPressMailParams($to, $subject, $body, $headers, $attachments, $locale)
        );

        $this->flushEmailParams();

        return $dispatched;
    }
}
