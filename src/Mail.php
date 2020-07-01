<?php

namespace Rumur\WordPress\Mailer;

/**
 * Class Mail
 *
 * @package Rumur\WordPress\Mailer
 *
 * @method static Compose to(string|string[]|\WP_User|\WP_User[] $email)
 * @method static Compose make(string|string[]|\WP_User|\WP_User[] $email, ?string $fromName = null, ?string $fromEmail = null)
 * @method static bool raw($to, string $subject, string $body, array $attachments = [], array $headers = [], ?\Closure $fn = null)
 * @method static Mailer from(string $fromName, $fromEmail = null)
 * @method static null|string fromName()
 * @method static null|string fromEmail()
 * @method static Mailer debug(\Closure $debugger)
 * @method static Mailer useCharset(string $charset)
 * @method static bool send(Mailable $mailable)
 */
class Mail
{
    /** @var Mailer */
    protected $mailer;

    /**
     * @var Mail
     */
    protected static $instance;

    /**
     * Mail constructor.
     * @param Mailer $mailer
     */
    protected function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * @param $method
     * @param $arguments
     * @return mixed|null
     */
    public static function __callStatic($method, $arguments)
    {
        if (! static::$instance) {
            static::$instance = new static(Mailer::instance());
        }

        $instance = static::$instance;

        if (! method_exists($instance->mailer, $method)) {
            return null;
        }

        return $instance->mailer->$method(...$arguments);
    }
}