<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;
    
    protected $table = 'products';

    protected $fillable = [
        'id',
        'vendor_id',
        'name',
        'description',
        'price',
        'stock',
        'created_at',
        'updated_at'
    ];

    public $increamenting = false;
    public $keyType = 'string';

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->id = $model->id ?? \Illuminate\Support\Str::uuid()->toString();
        });
    }
}
