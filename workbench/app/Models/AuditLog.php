<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Wayfinder\Attributes\WayfinderIgnore;

/**
 * @property int $id
 * @property string $internalNote
 */
#[WayfinderIgnore]
class AuditLog extends Model
{
    //
}
