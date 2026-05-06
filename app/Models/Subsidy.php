<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subsidy extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category',
        'label',
        'amount',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Scope to get only active subsidies
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
