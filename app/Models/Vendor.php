<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vendor extends Model
{
    use HasFactory;

    protected $table = 'vendors';

    protected $fillable = [
        'id',
        'user_id',
        'company_name',
        'contact_person',
        'phone',
        'email',
        'address',
    ];

    public $incrementing = false;
    public $keyType = 'string';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->id = $model->id ?? \Illuminate\Support\Str::uuid()->toString();
        });
    }
}
