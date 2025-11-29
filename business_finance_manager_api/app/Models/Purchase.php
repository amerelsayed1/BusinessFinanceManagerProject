<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Purchase extends Model
{
    protected $fillable = [
        'user_id',
        'account_id',
        'date',
        'supplier_name',
        'reference',
        'total_amount',
        'note',
        'invoice_image_path',
    ];

    protected $casts = [
        'date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    protected $appends = ['invoice_image_url'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function getInvoiceImageUrlAttribute()
    {
        return $this->invoice_image_path ? Storage::url($this->invoice_image_path) : null;
    }
}
