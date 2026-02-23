<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkshopForm extends Model
{
    protected $fillable = ['title', 'description', 'is_open', 'department_id'];

    protected $casts = ['is_open' => 'boolean'];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'form_id');
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class, 'form_id');
    }
}
