<?php

namespace Rumur\WPMailer;

class Compose
{
    use Traits\HasEmailListeners,
        Traits\HasAttachments;

    /**
     * @var Mailer
     */
    protected $mailer;

    /**
     * @var string|string[]|\WP_User
     */
    protected $to;

    /**
     * @var string|string[]|\WP_User
     */
    protected $cc;

    /**
     * @var string|string[]|\WP_User|\WP_User[]
     */
    protected $bcc;

    /**
     * The Local of the email.
     *
     * @var string
     */
    protected $locale;

    /**
     * PendingEmail constructor.
     *
     * @param Mailer $mailer
     */
    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * @return static
     * @var string|\WP_User $user
     *
     */
    public function to($user)
    {
        $this->to = $user;

        if (!$this->locale && $user instanceof \WP_User) {
            $this->locale($user->locale);
        }

        return $this;
    }

    /**
     * @return static
     * @var string|string[]|\WP_User|\WP_User[] $user Either email or on an array of emails or \WP_User or an array of \WP_User
     */
    public function cc($user)
    {
        $this->cc = $user;

        return $this;
    }

    /**
     * @return static
     * @var string|string[]|\WP_User|\WP_User[] $user Either email or on an array of emails or \WP_User or an array of \WP_User
     */
    public function bcc($user)
    {
        $this->bcc = $user;

        return $this;
    }

    /**
     * Sets the email locale.
     *
     * @param string $locale
     * @return static
     */
    public function locale(string $locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Sends an email when WordPress action is being fired.
     *
     * @param string $action
     * @param Mailable $mailable
     * @param int $priority
     *
     * @return static
     */
    public function sendOnAction(string $action, Mailable $mailable, int $priority = 10)
    {
        if (\current_action() === $action) {
            $this->send($mailable);
        }

        if (!\did_action($action)) {
            \add_action($action, function () use ($mailable) {
                $this->send($mailable);
            }, $priority);
        }

        return $this;
    }

    /**
     * Sends an email when a condition is true.
     *
     * @param bool $condition
     * @param Mailable $mailable
     *
     * @return static
     */
    public function sendWhen(bool $condition, Mailable $mailable)
    {
        if ($condition) {
            $this->send($mailable);
        }

        return $this;
    }

    /**
     * @param Mailable $mailable
     *
     * @return bool
     */
    public function send(Mailable $mailable): bool
    {
        return $this->mailer->send(
            $this->fill($mailable)
        );
    }

    /**
     * Populate the mailable with the attributes.
     *
     * @param Mailable $mailable
     *
     * @return Mailable
     */
    protected function fill(Mailable $mailable): Mailable
    {
        return $mailable
            ->to($this->to)->cc($this->cc)
            ->bcc($this->bcc)->locale($this->locale)
            ->setAttachments($this->attachments)
            ->onFailure($this->failedListeners)
            ->onSuccess($this->successListeners);
    }
}