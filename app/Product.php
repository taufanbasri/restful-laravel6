<?php

namespace App;

use App\Transformers\ProductTransformer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    public $transformer = ProductTransformer::class;

    const AVAILABE_PRODUCT = 'available';
    const UNAVAILABE_PRODUCT = 'unavailable';

    protected $dates = ['deleted_at'];
    protected $hidden = ['pivot'];
    protected $fillable = [
        'name', 'description', 'quantity', 'status', 'image', 'seller_id'
    ];

    public function isAvailable()
    {
        return $this->status == Product::AVAILABE_PRODUCT;
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }
}
