<?php

namespace Ahmed3bead\Settings;

use Illuminate\Database\Eloquent\Model;
use Ahmed3bead\Settings\Exceptions\ModelTypeException;

class EntryFilter
{
    /**
     * The owner of the settings entry.
     *
     * @var mixed
     */
    protected $model;

    /**
     * The group name of the settings entry.
     *
     * @var string
     */
    protected $group;

    /**
     * The exempted settings entries key values.
     *
     * @var array
     */
    protected $excepts = [];

    /**
     * Set the model owner of the settings entry.
     *
     * @param  mixed  $model
     * @return \Ahmed3bead\Settings\EntryFilter $this
     */
    public function setModel($model)
    {
        if (! $model instanceof Model) {
            throw ModelTypeException::invalid();
        }

        $this->model = $model;

        return $this;
    }

    /**
     * Set the group name of the settings entry.
     *
     * @param  string  $group
     * @return \Ahmed3bead\Settings\EntryFilter $this
     */
    public function setGroup($group)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Set the exempted settings entries.
     *
     * @param  string|array  $excepts
     * @return \Ahmed3bead\Settings\EntryFilter $this
     */
    public function setExcepts(...$excepts)
    {
        if (count($excepts) === 1 && is_array($excepts[0])) {
            $this->excepts = $excepts[0];
        } else {
            $this->excepts = $excepts;
        }

        return $this;
    }

    /**
     * Get the settings entry group name.
     *
     * @return string
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Get the settings entry owner instance.
     *
     * @return mixed
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Get list the of exempted settings entries key values.
     *
     * @return array
     */
    public function getExcepts()
    {
        return $this->excepts;
    }

    /**
     * Reset the current filter to default values.
     *
     * @return void
     */
    public function clear()
    {
        $this->model = null;

        $this->group = null;

        $this->excepts = [];
    }
}
