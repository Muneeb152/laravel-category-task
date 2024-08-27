<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = ['name', 'description', 'start_date', 'end_date', 'category_id', 'image'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
