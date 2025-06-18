<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    /**
     * Связанные с моделью таблица.
     */
    protected $table = 'events';

    /**
     * Поля, которые можно заполнять массово через вызов create() или update().
     */
    protected $fillable = [
        'project_id',
        'landing_id',
        'telegram_user_id',
        'event',
        'when',
    ];

    /**
     * Связь с моделью Project (многие-к-одному).
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Связь с моделью Landing (многие-к-одному).
     * Связь допускает то, что поля в landing_id может и не быть.
     */
    public function landing()
    {
        return $this->belongsTo(Landing::class);
    }
}
