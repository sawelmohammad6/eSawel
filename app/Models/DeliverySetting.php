<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliverySetting extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'standard_delivery_charge' => 'decimal:2',
            'express_delivery_charge' => 'decimal:2',
        ];
    }
}
