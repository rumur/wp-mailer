<?php

namespace Rumur\WPMailer\Traits;

trait HasAttachments
{
    /**
     * Attachments that will be passed to the `wp_mail`
     *
     * @var array
     */
    protected $attachments = [];

    /**
     * @param int[]|string[] $attachments Optional. Files to attach.
     *
     * @return static
     */
    public function setAttachments(array $attachments)
    {
        $this->attachments = $attachments;

        return $this;
    }

    /**
     * Adds an attachment file.
     *
     * @param int|string|int[]|string[] $attachment
     *
     * @return static
     */
    public function addAttachment($attachment)
    {
        $attachment = is_array($attachment) ? $attachment : func_get_args();

        $this->attachments = array_merge($this->attachments, $attachment);

        return $this;
    }

    /**
     * Gets the list of `attachments`
     *
     * @return array
     */
    public function attachments(): array
    {
        return $this->processAttachments($this->attachments);
    }

    /**
     * Converts ids to attachments relative paths.
     *
     * @param int[]|string[] $attachments
     *
     * @return array
     */
    protected function processAttachments(array $attachments): array
    {
        return array_map(static function ($attach) {

            if (is_int($attach) && $file = get_attached_file($attach)) {
                return $file;
            }

            return $attach;

        }, $attachments);
    }
}