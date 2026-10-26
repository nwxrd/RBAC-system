<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class RoleModule extends Pivot
{
    protected $table = 'role_modules';

    public $incrementing = false;
}
