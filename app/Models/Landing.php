<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Landing extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',           // название лендинга
        'url',            // URL лендинга
        'description',    // описание
        'short_description', // краткое описание
        'is_active',      // активация (boolean)
    ];

    /**
     * Связь с проектом.
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
