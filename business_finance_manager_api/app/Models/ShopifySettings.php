<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ShopifySettings extends Model
{
    protected $fillable = [
        'user_id',
        'store_domain',
        'api_token',
        'is_connected',
        'last_sync_at',
    ];

    protected $casts = [
        'is_connected' => 'boolean',
        'last_sync_at' => 'datetime',
    ];

    protected $hidden = [
        'api_token',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Encrypt API token
    protected function apiToken(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? decrypt($value) : null,
            set: fn ($value) => $value ? encrypt($value) : null,
        );
    }
}
