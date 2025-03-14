<?php

namespace Ahmed3bead\Settings\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Ahmed3bead\Settings\HasSettings;

class User extends Model
{
    use HasSettings;

    protected $guarded = [];
}
