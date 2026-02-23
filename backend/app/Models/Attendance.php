<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $table = 'attendance';

    protected $fillable = ['student_id', 'form_id', 'attended_at'];

    protected $casts = ['attended_at' => 'datetime'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(WorkshopForm::class, 'form_id');
    }
}
