<?php

namespace Rumur\WPMailer;

class Dispatcher
{
    use Traits\HasEmailListeners;

    /**
     * @var WordPressMailParams
     */
    protected $params;

    /**
     * Holds the origin charset of @global $phpmailer
     *
     * @var string
     */
    protected $originPhpMailerCharset;

    /**
     * @var bool
     */
    protected $isSent = false;

    /**
     * @var bool
     */
    protected $isFailed = false;

    /**
     * @var string
     */
    protected $fromName;

    /**
     * @var string
     */
    protected $fromEmail;

    /**
     * @var string
     */
    protected $charset = 'UTF-8';

    /**
     * @return bool
     */
    public function isSent(): bool
    {
        return $this->isSent;
    }

    /**
     * @return bool
     */
    public function isFailed(): bool
    {
        return $this->isFailed;
    }

    /**
     * Adds a specific charset to an email.
     *
     * @param string $charset
     * @return static
     */
    public function withCharset(string $charset)
    {
        $this->charset = $charset;

        return $this;
    }

    /**
     * Adds a specific from to an email.
     *
     * @param array $from
     * @return static
     */
    public function withFrom(array $from)
    {
        $this->fromName = $from['name'] ?? null;
        $this->fromEmail = $from['email'] ?? null;

        return $this;
    }

    /**
     * @return string|null
     */
    public function fromName(): ?string
    {
        return $this->fromName;
    }

    /**
     * @return string|null
     */
    public function fromEmail(): ?string
    {
        return $this->fromEmail;
    }

    /**
     * Dispatches email.
     *
     * @param WordPressMailParams $params
     * @return bool
     */
    public function dispatch(WordPressMailParams $params): bool
    {
        /**
         * @var WordPressMailParams $params
         *
         * @var Dispatcher $dispatcher
         *
         * @return WordPressMailParams
         */
        $this->params = apply_filters('rumur/wp-mailer/email_params', $params, $this);

        /**
         * Double check if `to` recipient was not corrupted.
         */
        if (null === $this->params->to) {
            return false;
        }

        $this->setHelperActionsBeforeSend();

        $this->isSent = wp_mail(
            $this->params->to,
            $this->params->subject,
            $this->params->body,
            $this->params->headers,
            $this->params->attachments
        );

        $this->performSuccessAction()
            ->removeHelperActionsAfterSend();

        /**
         * @var WordPressMailParams $params;
         * @var Dispatcher $dispatcher;
         */
        do_action('rumur/wp-mailer/dispatched', $params, $this);

        return $this->isSent;
    }

    /**
     * Gather together all filters and actions that need to be performed due to send process.
     *
     * @return void
     */
    protected function setHelperActionsBeforeSend(): void
    {
        $this->setFailedListeners();
        $this->setCharsetForMailer();
        $this->setLocaleForMailer();
        $this->setFilterFromForMailer();
    }

    /**
     * Remove all filters and actions after a mail being sent.
     *
     * @return void
     */
    protected function removeHelperActionsAfterSend(): void
    {
        $this->removeFailedListeners();
        $this->restoreCharsetForMailer();
        $this->restoreLocaleForMailer();
        $this->removeFilterFromForMailer();
    }

    /**
     * Allow's to send letters such as ÖÕЁЙ etc.
     *
     * @hooked add_filter('wp_mail_charset')
     */
    protected function setCharsetForMailer(): void
    {
        mb_internal_encoding($this->charset);

        \add_filter('wp_mail_charset', [$this, 'encodingHelperForMailer']);
    }

    /**
     * Helps to restore the charset for PHPMailer;
     *
     * @global $phpmailer
     * @hooked remove_filter('wp_mail_charset')
     */
    protected function restoreCharsetForMailer(): void
    {
        global $phpmailer;

        $phpmailer->Encoding = $this->originPhpMailerCharset;

        \remove_filter('wp_mail_charset', [$this, 'encodingHelperForMailer']);
    }

    /**
     * Switches the locale of the email.
     */
    protected function setLocaleForMailer(): void
    {
        if (function_exists('switch_to_locale') && ($locale = $this->params->locale)) {
            \switch_to_locale($locale);
        }
    }

    /**
     * Switches back the locale of the email.
     */
    protected function restoreLocaleForMailer(): void
    {
        if (function_exists('is_locale_switched') && $this->params->locale && \is_locale_switched()) {
            \restore_previous_locale();
        }
    }

    /**
     * Sets an filter that helps to change the `from_email` or `from_name`
     */
    public function setFilterFromForMailer(): void
    {
        if ($this->fromEmail()) {
            \add_filter('wp_mail_from', [$this, 'fromEmail'], 500);
        }

        if ($this->fromName()) {
            \add_filter('wp_mail_from_name', [$this, 'fromName'], 500);
        }
    }

    /**
     * Removes a filter that was meant to change `from_email` or `from_name`
     */
    protected function removeFilterFromForMailer(): void
    {
        if ($this->fromEmail()) {
            \remove_filter('wp_mail_from', [$this, 'fromEmail'], 500);
        }

        if ($this->fromName()) {
            \remove_filter('wp_mail_from_name', [$this, 'fromName'], 500);
        }
    }

    /**
     * @return $this
     */
    protected function performSuccessAction(): self
    {
        if ($this->isSent()) {

            $should_proceed = true;

            foreach ($this->successListeners() as $listener) {

                // Stop of calling next listeners.
                if (false === $should_proceed) {
                    break;
                }

                if (is_string($listener) && class_exists($listener)) {

                    $l = new $listener;

                    if (method_exists($l, 'handle')) {
                        $should_proceed = $l->handle($this->params, $this);
                    }
                }

                if (is_callable($listener)) {
                    $should_proceed = call_user_func($listener, $this->params, $this);
                }
            }
        }

        return $this;
    }

    /**
     * @param \WP_Error $error
     */
    public function performFailedAction(\WP_Error $error): void
    {
        $this->isFailed = true;

        $should_proceed = true;

        foreach ($this->failedListeners() as $listener) {

            // Stop of calling next listeners.
            if (false === $should_proceed) {
                break;
            }

            if (is_string($listener) && class_exists($listener)) {

                $l = new $listener;

                if (method_exists($l, 'handle')) {
                    $should_proceed = $l->handle($error, $this->params, $this);
                }
            }

            if (is_callable($listener)) {
                $should_proceed = call_user_func($listener, $error, $this->params, $this);
            }
        }
    }

    /**
     * @return $this
     */
    protected function setFailedListeners(): self
    {
        \add_action('wp_mail_failed', [$this, 'performFailedAction']);

        return $this;
    }

    /**
     * @return $this
     */
    protected function removeFailedListeners(): self
    {
        \remove_action('wp_mail_failed', [$this, 'performFailedAction']);

        return $this;
    }

    /**
     * @global $phpmailer
     * @param string $blog_charset
     * @return string
     */
    public function encodingHelperForMailer(string $blog_charset = 'UTF-8'): string
    {
        global $phpmailer;

        $charset = $this->charset ?? $blog_charset;

        $this->originPhpMailerCharset = $phpmailer->Encoding;

        $phpmailer->Encoding = (!strcasecmp($charset, 'UTF-8') ? 'base64' : '8bit');

        return $charset;
    }
}