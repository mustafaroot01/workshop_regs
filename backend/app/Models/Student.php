<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    protected $fillable = [
        'form_id', 'department_id', 'name', 'gender',
        'email', 'phone', 'stage', 'study_type', 'description', 'qr_token',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(WorkshopForm::class, 'form_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function interests(): BelongsToMany
    {
        return $this->belongsToMany(Interest::class, 'student_interests');
    }

    public function attendance(): HasOne
    {
        return $this->hasOne(Attendance::class);
    }
}
