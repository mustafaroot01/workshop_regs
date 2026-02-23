<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interest extends Model
{
    protected $fillable = ['name', 'department_id'];

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_interests');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
