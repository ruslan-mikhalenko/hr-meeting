<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'link',
        'yandex_metric_id',
        'goal_id',
        'measurement_protocol_token',
        'landing_url',
    ];

    /**
     * Связь с клиентом (таблица `clients`).
     */
    public function client()
    {
        return $this->belongsTo(Client::class, 'user_id', 'user_id');
    }
}
