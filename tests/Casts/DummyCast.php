<?php

namespace Ahmed3bead\Settings\Tests\Casts;

use Ahmed3bead\Settings\Contracts\Castable;

class DummyCast implements Castable
{
    public function set(mixed $payload): string
    {
        return 'dummy value';
    }

    public function get(mixed $payload): string
    {
        return 'dummy value';
    }
}
