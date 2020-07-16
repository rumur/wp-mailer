<?php

namespace Rumur\WordPress\Mailer;

class Utils
{
    /**
     * @param string|string[]|\WP_User|\WP_User[] $emails
     *
     * @return string
     */
    public static function sanitizeEmails($emails): string
    {
        if (is_array($emails)) {
            $emails = array_map(static function ($email) {
                return $email instanceof \WP_User ? $email->user_email : $email;
            }, $emails);
        }

        if ($emails instanceof \WP_User) {
            $emails = [$emails->user_email];
        }

        if (!is_array($emails)) {
            $emails = explode(',', $emails);
        }

        $emails = array_filter(
            array_map('trim', $emails),
            'is_email'
        );

        return implode(',', $emails);
    }

    /**
     * Get the class "basename" of the given object / class.
     *
     * @param  string|object  $class
     * @return string
     */
    public static function classBasename($class): string
    {
        $class = is_object($class) ? get_class($class) : $class;

        return basename(str_replace('\\', '/', $class));
    }
}
