<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = ['name', 'logo_path'];
    protected $appends = ['logo_url'];

    public function getLogoUrlAttribute()
    {
        return $this->logo_path ? asset('storage/' . $this->logo_path) : null;
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function interests(): HasMany
    {
        return $this->hasMany(Interest::class);
    }
}
