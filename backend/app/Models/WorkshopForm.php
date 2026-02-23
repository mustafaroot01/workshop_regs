<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkshopForm extends Model
{
    protected $fillable = ['title', 'description', 'is_open'];

    protected $casts = ['is_open' => 'boolean'];

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'form_id');
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class, 'form_id');
    }
}
