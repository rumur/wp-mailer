<?php

namespace Rumur\WordPress\Mailer;

use Illuminate\Support\Facades\Log;
use PhpParser\Node\Expr\Closure;
use Rumur\WordPress\Scheduling\{Scheduler, Schedule};

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
     * Sends an email later when time has come.
     *
     * @param \DateTimeInterface|int|string $when The time when you need to run this email,
     *                                            note that this functionality uses WordPress Scheduling system.
     * Example: @link https://www.php.net/manual/en/datetime.formats.relative.php
     *  - 'tomorrow'
     *  - 'next month'
     *  - 'next week'
     *  - 'last day of +2 months'
     *  - 'last day of next month'
     *  - 'last day of next month noon'
     * @param Mailable $mailable   A mailable instance.
     *
     * @uses Schedule
     *
     * @return static
     */
    public function sendLater($when, Mailable $mailable)
    {
        if (! \class_exists('Rumur\\WordPress\\Scheduling\\Schedule')) {
            throw new \RuntimeException(__FUNCTION__ . ' requires `Rumur\WordPress\Scheduling` package to operate.');
        }

        $timestamp = false;

        if (\is_string($when)) {
            $timestamp = \strtotime($when, Schedule::intervals()->now());
        }

        if (\is_int($when)) {
            $timestamp = $when;
        }

        if ($when instanceof \DateTimeInterface) {
            $timestamp = $when->getTimestamp();
        }

        if (! $timestamp) {
            throw new \InvalidArgumentException(
                sprintf('Seems `$when: %s` has a wrong format or could not be converted to a timestamp.', $when)
            );
        }

        // In order to avoid an error "Using $this when not in object context"
        // We just making a reference of the Compose via `use`.
        $compose = $this;

        Schedule::call(static function () use ($compose, $mailable) {
            $compose->send($mailable);
        })->registerSingular('custom', 0, $timestamp);

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