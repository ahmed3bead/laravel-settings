# Laravel Settings

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ahmedebead/laravel-settings.svg?style=flat-square)](https://packagist.org/packages/ahmedebead/laravel-settings)
[![Total Downloads](https://img.shields.io/packagist/dt/ahmedebead/laravel-settings.svg?style=flat-square)](https://packagist.org/packages/ahmedebead/laravel-settings)
[![License](https://img.shields.io/packagist/l/ahmedebead/laravel-settings.svg?style=flat-square)](LICENSE.md)

A simple, flexible settings package for Laravel. Store application-wide settings or settings scoped to any Eloquent model. Values are automatically type-cast when stored and restored when retrieved.

**Requires:** PHP 8.2+ · Laravel 11 / 12 / 13

---

## Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Basic Usage](#basic-usage)
  - [Storing settings](#storing-settings)
  - [Retrieving settings](#retrieving-settings)
  - [Checking existence](#checking-existence)
  - [Deleting settings](#deleting-settings)
- [Scoping](#scoping)
  - [Model-specific settings](#model-specific-settings)
  - [Groups](#groups)
  - [Excluding keys](#excluding-keys)
  - [Combining scopes](#combining-scopes)
- [HasSettings trait](#hassettings-trait)
- [The `settings()` helper](#the-settings-helper)
- [Caching](#caching)
- [Type Casting](#type-casting)
  - [Built-in casts](#built-in-casts)
  - [Custom casts](#custom-casts)
- [Custom Repository](#custom-repository)
- [Testing](#testing)

---

## Installation

```bash
composer require ahmedebead/laravel-settings
```

The service provider and `Settings` facade are registered automatically via package auto-discovery.

Publish the config file:

```bash
php artisan vendor:publish --provider="Ahmed3bead\Settings\SettingsServiceProvider" --tag="config"
```

Publish and run the migration:

```bash
php artisan vendor:publish --provider="Ahmed3bead\Settings\SettingsServiceProvider" --tag="migrations"
php artisan migrate
```

> Running `vendor:publish --tag="migrations"` a second time is safe — it will not create a duplicate migration file if one already exists.

---

## Configuration

After publishing, the config file lives at `config/settings.php`.

```php
return [

    // Which repository driver to use (default: database)
    'default' => env('SETTINGS_REPOSITORY_DEFAULT', 'database'),

    'repositories' => [
        'database' => [
            'handler'    => Ahmed3bead\Settings\Repositories\DatabaseRepository::class,
            'connection' => null,   // null = use the app's default DB connection
            'table'      => 'settings',
        ],
    ],

    'cache' => [
        'enabled' => env('SETTINGS_CACHE_ENABLED', false),
        'store'   => null,   // null = use the app's default cache store
        'prefix'  => null,
    ],

    'casts' => [
        Carbon\Carbon::class       => Ahmed3bead\Settings\Casts\CarbonCast::class,
        Carbon\CarbonPeriod::class => Ahmed3bead\Settings\Casts\CarbonPeriodCast::class,
    ],

];
```

---

## Basic Usage

You can use the `Settings` facade, the `settings()` helper, or the `HasSettings` trait — all three give you the same API.

### Storing settings

```php
use Settings;

// Single key
Settings::set('timezone', 'UTC');

// Multiple keys at once
Settings::set([
    'timezone' => 'UTC',
    'language' => 'en',
    'per_page' => 25,
]);
```

Values can be any PHP type — strings, integers, booleans, arrays, and objects (see [Type Casting](#type-casting)):

```php
use Carbon\Carbon;

Settings::set('launched_at', Carbon::now());   // stored and restored as Carbon
Settings::set('flags', ['feature_x' => true]); // plain arrays work too
```

Nested arrays are fully supported. Casts are applied recursively:

```php
Settings::set('report', [
    'generated_at' => Carbon::now(),   // will be cast
    'filters' => [
        'from' => Carbon::yesterday(), // also cast, however deep
        'limit' => 100,
    ],
]);
```

---

### Retrieving settings

```php
// Single key — returns the value or null
$tz = Settings::get('timezone');

// With a default value when the key does not exist
$tz = Settings::get('timezone', 'UTC');

// Multiple keys at once — returns an associative array
$values = Settings::get(['timezone', 'language']);
// ['timezone' => 'UTC', 'language' => 'en']

// Multiple keys with a shared default for any missing ones
$values = Settings::get(['timezone', 'missing_key'], 'default');
// ['timezone' => 'UTC', 'missing_key' => 'default']

// All settings (global scope — no model, no group)
$all = Settings::all();
```

---

### Checking existence

`exists()` performs a database count query, so it correctly reports `true` even when a key is stored with a `null` value.

```php
if (Settings::exists('timezone')) {
    // key is in the database
}

// Returns false for keys that have never been set
Settings::exists('unknown_key'); // false

// Scoped to a model or group (see Scoping section)
Settings::for($user)->exists('theme');
Settings::group('billing')->exists('vat_rate');
```

---

### Deleting settings

```php
// Single key
Settings::forget('timezone');

// Multiple keys
Settings::forget(['timezone', 'language']);
```

---

## Scoping

Every method (`set`, `get`, `all`, `exists`, `forget`) respects the active scope. Scopes are fluent and do not leak — each call is independent.

### Model-specific settings

Attach settings to any Eloquent model with `for()`. Two users with the same key never interfere with each other.

```php
$user1 = User::find(1);
$user2 = User::find(2);

Settings::for($user1)->set('theme', 'dark');
Settings::for($user2)->set('theme', 'light');

Settings::for($user1)->get('theme'); // 'dark'
Settings::for($user2)->get('theme'); // 'light'

Settings::for($user1)->all();
// ['theme' => 'dark']
```

> Settings stored without `for()` are global and separate from model-specific ones.

---

### Groups

Use `group()` to namespace settings into logical categories.

```php
Settings::group('email')->set('driver', 'smtp');
Settings::group('email')->set('from', 'hello@example.com');

Settings::group('billing')->set('vat_rate', 20);

Settings::group('email')->all();
// ['driver' => 'smtp', 'from' => 'hello@example.com']

Settings::group('billing')->all();
// ['vat_rate' => 20]

// Global settings (no group) are unaffected
Settings::all();
// []
```

---

### Excluding keys

Use `except()` to skip one or more keys when calling `all()`:

```php
Settings::except('secret_key')->all();

Settings::except('k1', 'k2')->all();

Settings::except(['k1', 'k2'])->all();
```

---

### Combining scopes

All scoping methods can be chained in any order:

```php
// Model + group
Settings::for($user)->group('preferences')->set('lang', 'ar');

// Read it back
Settings::for($user)->group('preferences')->get('lang');

// All settings for a user in a group, excluding one key
Settings::for($user)->group('preferences')->except('lang')->all();

// forget is scoped too — only removes 'lang' from group 'preferences' for $user
Settings::for($user)->group('preferences')->forget('lang');
```

---

## HasSettings Trait

Add the `HasSettings` trait to any Eloquent model to get a `settings()` method that automatically scopes to that model instance. It is equivalent to calling `Settings::for($this)`.

```php
use Ahmed3bead\Settings\HasSettings;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasSettings;
}
```

```php
$user = User::find(1);

$user->settings()->set('theme', 'dark');
$user->settings()->set(['lang' => 'en', 'per_page' => 20]);

$user->settings()->get('theme');         // 'dark'
$user->settings()->all();               // ['theme' => 'dark', 'lang' => 'en', 'per_page' => 20]
$user->settings()->exists('theme');     // true
$user->settings()->forget('theme');

// Group scoping still works
$user->settings()->group('billing')->set('vat', 20);
$user->settings()->group('billing')->get('vat'); // 20
```

---

## The `settings()` helper

A global `settings()` helper is included. It returns the same `Settings` instance as the facade.

```php
settings()->set('key', 'value');
settings()->get('key');
settings()->for($user)->set('theme', 'dark');
```

---

## Caching

Enable caching in `config/settings.php` or via an environment variable:

```dotenv
SETTINGS_CACHE_ENABLED=true
```

Or in the config file directly:

```php
'cache' => [
    'enabled' => true,
    'store'   => 'redis',  // any cache store configured in config/cache.php
    'prefix'  => 'app',
],
```

**How it works:**

- `get()` and `all()` cache results forever until the underlying data changes.
- `set()` and `forget()` automatically invalidate both the key-specific cache entry and the `all()` cache entry.
- Cache keys include the model class, model primary key, group, and excluded keys — so two different users always get isolated cache entries.

---

## Type Casting

When you store an object, the package looks up the matching cast handler and serializes the value. On retrieval, the original object type is restored transparently.

### Built-in casts

| PHP type | Stored as | Restored as |
|---|---|---|
| `Carbon\Carbon` | ISO-8601 date string | `Carbon\Carbon` |
| `Carbon\CarbonPeriod` | `{start, end}` ISO strings | `Carbon\CarbonPeriod` |
| Everything else | JSON-encoded as-is | Decoded as-is |

### Custom casts

**Step 1 — Create a cast class** implementing `Castable`:

```php
use Ahmed3bead\Settings\Contracts\Castable;

class MoneyCast implements Castable
{
    /**
     * Called when the value is being saved.
     * Return any JSON-serializable value.
     */
    public function set(mixed $payload): array
    {
        return [
            'amount'   => $payload->getAmount(),
            'currency' => $payload->getCurrency(),
        ];
    }

    /**
     * Called when the value is being loaded.
     * Reconstruct and return the original object.
     */
    public function get(mixed $payload): Money
    {
        return new Money($payload['amount'], $payload['currency']);
    }
}
```

**Step 2 — Register it** in `config/settings.php`:

```php
'casts' => [
    Money::class => MoneyCast::class,
],
```

**Step 3 — Use it normally:**

```php
Settings::set('price', new Money(1000, 'USD'));

$price = Settings::get('price'); // Money instance
```

**Passing constructor arguments to a cast:**

If your cast needs configuration, register an object instance instead of a class name:

```php
'casts' => [
    MyType::class => new MyCast('some-parameter'),
],
```

---

## Custom Repository

The database driver is the default, but you can replace it with any storage backend.

**Step 1 — Create a repository class** that extends the abstract base and implements all required methods:

```php
use Ahmed3bead\Settings\Repositories\Repository;

class RedisRepository extends Repository
{
    public function get(string|array $key, mixed $default = null): mixed
    {
        // read from Redis using $this->entryFilter for scoping
    }

    public function set(string|array $key, mixed $value = null): void
    {
        // write to Redis
    }

    public function forget(string|array $key): void
    {
        // delete from Redis
    }

    public function all(): array
    {
        // return all entries matching $this->entryFilter
    }

    public function exists(string $key): bool
    {
        // return true if $key exists
    }
}
```

> `$this->entryFilter` gives you the active `EntryFilter` instance with `getModel()`, `getGroup()`, and `getExcepts()`.

**Step 2 — Register it** in `config/settings.php`:

```php
'repositories' => [
    'database' => [ ... ],

    'redis' => [
        'handler' => App\Settings\RedisRepository::class,
    ],
],
```

**Step 3 — Set it as default:**

```php
'default' => 'redis',
```

---

## Testing

```bash
composer test
```

---

## License

The MIT License (MIT). Please see [LICENSE.md](LICENSE.md) for more information.
