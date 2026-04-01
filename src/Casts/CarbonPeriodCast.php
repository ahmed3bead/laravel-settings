<?php

namespace Ahmed3bead\Settings\Casts;

use Carbon\CarbonPeriod;
use Ahmed3bead\Settings\Contracts\Castable;

class CarbonPeriodCast implements Castable
{
    /**
     * Apply casting rules when storing the payload into the settings repository.
     *
     * @param  \Carbon\CarbonPeriod  $payload
     */
    public function set(mixed $payload): array
    {
        return [
            'start' => $payload->getStartDate()->toIso8601String(),
            'end' => $payload->getEndDate()->toIso8601String(),
        ];
    }

    /**
     * Apply casting rules when storing the payload into the settings repository.
     *
     * @param  array  $payload
     */
    public function get(mixed $payload): CarbonPeriod
    {
        return new CarbonPeriod(
            $payload['start'],
            $payload['end']
        );
    }
}
