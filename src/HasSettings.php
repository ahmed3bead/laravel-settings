<?php

namespace Ahmed3bead\Settings;

use Settings;
use Ahmed3bead\Settings\Settings as SettingsManager;

trait HasSettings
{
    /**
     * Retrieve the settings manager instance for this model.
     */
    public function settings(): SettingsManager
    {
        return Settings::for($this);
    }
}
