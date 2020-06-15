<?php

namespace Rumur\WPMailer\Traits;

trait HasEmailListeners
{
    /**
     * The list of listeners that fill be fired when the mail is failed.
     *
     * @var array
     */
    protected $failedListeners = [];

    /**
     * The list of listeners that fill be fired when the mail is sent.
     *
     * @var array
     */
    protected $successListeners = [];

    /**
     * @param \Closure|\Closure[] $listeners
     * @return static
     */
    public function onFailure($listeners)
    {
        $listeners = is_array($listeners) ? $listeners : func_get_args();

        $this->failedListeners = array_merge($this->failedListeners, $listeners);

        return $this;
    }

    /**
     * @return array
     */
    public function failedListeners(): array
    {
        return $this->failedListeners;
    }

    /**
     * @param \Closure|\Closure[] $listeners
     * @return static
     */
    public function onSuccess($listeners)
    {
        $listeners = is_array($listeners) ? $listeners : func_get_args();

        $this->successListeners = array_merge($this->successListeners, $listeners);

        return $this;
    }

    /**
     * @return array
     */
    public function successListeners(): array
    {
        return $this->successListeners;
    }
}