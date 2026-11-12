<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncompleteOrder extends Model
{
    protected $fillable = [
        'session_id', 'name', 'phone', 'email', 'address', 'cart', 'total', 'status', 'admin_note', 'is_converted',
    ];

    protected function casts(): array
    {
        return [
            'cart' => 'array',
            'total' => 'decimal:2',
            'is_converted' => 'boolean',
        ];
    }
}
