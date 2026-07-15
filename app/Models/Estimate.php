<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Estimate extends Model
{
    use SoftDeletes, Blameable;

    protected $table = 'estimates';
    protected $primaryKey = 'estimate_id';
    public $incrementing = true;

    protected $fillable = [
        'customer_id',
        'user_id',
        'product_id',
        'template_id',
        'estimate_name',
        'estimate_no',
        'type',
        'estimate_date',
        'valid_until',
        'currency',
        'quantity',
        'price',
        'price_mode',
        'solar_structure_charges',
        'solar_meter_charges',
        'total',
        'gst',
        'gst_amount',
        'gst_breakdown',
        'discount',
        'subsidy_amount',
        'other_charges',
        'amount',
        'product_name',
        'status',
        'comment',
        'attach_file',
        'customer_docs',
        'generation_data',
        'is_quotation',
        'isDeleted',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'estimate_date' => 'date',
        'valid_until' => 'date',
        'quantity' => 'integer',
        'price' => 'float',
        'solar_structure_charges' => 'float',
        'total' => 'float',
        'gst' => 'float',
        'gst_amount' => 'float',
        'gst_breakdown' => 'array',
        'discount' => 'float',
        'subsidy_amount' => 'float',
        'other_charges' => 'float',
        'amount' => 'float',
        'customer_docs' => 'array',
        'generation_data' => 'array',
        'is_quotation' => 'boolean',
        'isDeleted' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
