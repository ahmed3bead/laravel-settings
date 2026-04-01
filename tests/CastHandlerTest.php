<?php

namespace Ahmed3bead\Settings\Tests;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Arr;
use Ahmed3bead\Settings\CastHandler;
use Ahmed3bead\Settings\Tests\Casts\AnotherCast;
use Ahmed3bead\Settings\Tests\Casts\DummyCast;
use Ahmed3bead\Settings\Tests\Models\DummyClass;

class CastHandlerTest extends TestCase
{
    public function test_it_can_apply_the_appropiate_cast_handler()
    {
        $castHandler = new CastHandler();

        $date = Carbon::create();
        $payload = $castHandler->handle($date);
        $this->assertSame(Carbon::class, $payload['__sc__']);

        $period = CarbonPeriod::create(now(), now());
        $payload = $castHandler->handle($period);
        $this->assertSame(CarbonPeriod::class, $payload['__sc__']);

        $payload = $castHandler->handle([
            'k1' => $date,
            'k2' => $period,
        ]);

        $this->assertSame('Carbon\Carbon', Arr::get($payload, 'k1')['__sc__']);
        $this->assertSame('Carbon\CarbonPeriod', Arr::get($payload, 'k2')['__sc__']);

        $this->app['config']->set('settings.casts', [
            DummyClass::class => DummyCast::class,
        ]);

        $dummyInstance = new DummyClass();
        $payload = $castHandler->handle($dummyInstance);
        $this->assertSame('dummy value', $payload['__sv__']);
        $this->assertSame(DummyClass::class, $payload['__sc__']);
    }

    public function test_it_can_accept_objects_as_casts_handler_in_settings_file()
    {
        $castHandler = new CastHandler();

        $this->app['config']->set('settings.casts', [
            DummyClass::class => new AnotherCast('t1'),
        ]);

        $dummyInstance = new DummyClass();
        $payload = $castHandler->handle($dummyInstance);
        $this->assertSame('v1', $payload['__sv__']);

        $this->app['config']->set('settings.casts', [
            DummyClass::class => new AnotherCast('t2'),
        ]);

        $dummyInstance = new DummyClass();
        $payload = $castHandler->handle($dummyInstance);
        $this->assertSame('v2', $payload['__sv__']);
    }
}
