<?php

namespace Ahmed3bead\Settings\Tests;

use Carbon\Carbon;
use Settings;
use Ahmed3bead\Settings\Tests\Models\User;

class SettingsBugRegressionTest extends TestCase
{
    // TEST-1: Cache isolation per model ID (BUG-C1)
    public function test_cache_is_isolated_per_model_instance()
    {
        $this->app['config']->set('settings.cache.enabled', true);

        $user1 = User::create(['name' => 'Alice']);
        $user2 = User::create(['name' => 'Bob']);

        Settings::for($user1)->set('theme', 'dark');
        Settings::for($user2)->set('theme', 'light');

        $this->assertSame('dark', Settings::for($user1)->get('theme'));
        $this->assertSame('light', Settings::for($user2)->get('theme'));
    }

    // TEST-1: all() cache is invalidated after set() (BUG-C4)
    public function test_all_cache_is_invalidated_after_set()
    {
        $this->app['config']->set('settings.cache.enabled', true);

        Settings::set('k1', 'v1');
        $this->assertCount(1, Settings::all());

        Settings::set('k2', 'v2');
        $all = Settings::all();

        $this->assertCount(2, $all);
        $this->assertSame('v2', $all['k2']);
    }

    // TEST-1: all() cache is invalidated after forget() (BUG-C4)
    public function test_all_cache_is_invalidated_after_forget()
    {
        $this->app['config']->set('settings.cache.enabled', true);

        Settings::set(['k1' => 'v1', 'k2' => 'v2']);
        $this->assertCount(2, Settings::all());

        Settings::forget('k1');
        $this->assertCount(1, Settings::all());
    }

    // TEST-2: forget() is scoped to group filter (BUG-C3)
    public function test_forget_with_group_only_deletes_from_that_group()
    {
        Settings::group('g1')->set('k1', 'val_g1');
        Settings::group('g2')->set('k1', 'val_g2');

        $this->assertSame('val_g1', Settings::group('g1')->get('k1'));
        $this->assertSame('val_g2', Settings::group('g2')->get('k1'));

        Settings::group('g1')->forget('k1');

        $this->assertNull(Settings::group('g1')->get('k1'));
        $this->assertSame('val_g2', Settings::group('g2')->get('k1'));
    }

    // TEST-3: exists() with null value (BUG-M1)
    public function test_exists_returns_true_for_key_stored_with_null_value()
    {
        Settings::set('nullable_key', null);

        $this->assertTrue(Settings::exists('nullable_key'));
        $this->assertFalse(Settings::exists('nonexistent_key'));
    }

    // TEST-5: Filter is cleared after get() cache hit (BUG-M5)
    public function test_filter_is_cleared_after_cached_get()
    {
        $this->app['config']->set('settings.cache.enabled', true);

        $user = User::create(['name' => 'Charlie']);
        Settings::for($user)->set('k1', 'user_val');
        Settings::set('k1', 'global_val');

        // First call populates cache
        Settings::for($user)->get('k1');

        // Second call: filter must NOT leak into a plain get()
        $result = Settings::get('k1');
        $this->assertSame('global_val', $result);
    }

    // TEST-5: Filter is cleared after get() even when cache miss path runs
    public function test_filter_is_cleared_after_non_cached_get()
    {
        $user = User::create(['name' => 'Dave']);
        Settings::for($user)->set('k1', 'user_val');
        Settings::set('k1', 'global_val');

        Settings::for($user)->get('k1');

        $result = Settings::get('k1');
        $this->assertSame('global_val', $result);
    }

    // TEST-3: exists() is scoped to group (BUG-M1 complementary)
    public function test_exists_is_scoped_to_group()
    {
        Settings::group('g1')->set('k1', 'v1');

        $this->assertTrue(Settings::group('g1')->exists('k1'));
        $this->assertFalse(Settings::exists('k1'));
    }

    // TEST-3: exists() is scoped to model (BUG-M1 complementary)
    public function test_exists_is_scoped_to_model()
    {
        $user = User::create(['name' => 'Eve']);
        Settings::for($user)->set('k1', 'v1');

        $this->assertTrue(Settings::for($user)->exists('k1'));
        $this->assertFalse(Settings::exists('k1'));
    }

    // TEST-7: HasSettings trait read/write integration
    public function test_has_settings_trait_can_read_and_write_settings()
    {
        $user = User::create(['name' => 'Frank']);

        $user->settings()->set('theme', 'dark');
        $user->settings()->set('lang', 'en');

        $this->assertSame('dark', $user->settings()->get('theme'));
        $this->assertSame('en', $user->settings()->get('lang'));
        $this->assertCount(2, $user->settings()->all());

        $user->settings()->forget('theme');
        $this->assertFalse($user->settings()->exists('theme'));
        $this->assertCount(1, $user->settings()->all());
    }

    // BUG-M4: user data with old $value/$cast keys no longer collides with internal envelope
    public function test_user_data_with_dollar_value_and_cast_keys_does_not_collide()
    {
        Settings::set('config', ['$value' => 'user_value', '$cast' => 'user_cast']);

        $result = Settings::get('config');

        // Should come back as a plain array, not be mistaken for a cast envelope
        $this->assertIsArray($result);
        $this->assertSame('user_value', $result['$value']);
        $this->assertSame('user_cast', $result['$cast']);
    }

    // BUG-C5: created_at is preserved across updates
    public function test_created_at_is_preserved_on_update()
    {
        Settings::set('k1', 'original');

        $createdAt = \Illuminate\Support\Facades\DB::table('settings')
            ->where('key', 'k1')
            ->value('created_at');

        // Travel forward in time so updated_at will differ
        $this->travel(5)->seconds();

        Settings::set('k1', 'updated');

        $row = \Illuminate\Support\Facades\DB::table('settings')
            ->where('key', 'k1')
            ->first();

        $this->assertSame($createdAt, $row->created_at);
        $this->assertNotSame($createdAt, $row->updated_at);
        $this->assertSame(1, \Illuminate\Support\Facades\DB::table('settings')->where('key', 'k1')->count());
    }

    // TEST-8: CastHandlerException::invalid is thrown for invalid handler
    public function test_cast_handler_exception_is_thrown_for_invalid_handler()
    {
        $castHandler = new \Ahmed3bead\Settings\CastHandler();

        $this->app['config']->set('settings.casts', [
            \Ahmed3bead\Settings\Tests\Models\DummyClass::class => 'not_a_valid_handler',
        ]);

        $this->expectException(\Ahmed3bead\Settings\Exceptions\CastHandlerException::class);

        $castHandler->handle(new \Ahmed3bead\Settings\Tests\Models\DummyClass());
    }
}
