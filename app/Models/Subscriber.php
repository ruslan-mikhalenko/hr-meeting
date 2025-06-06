<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscriber extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'user_id',
        'telegram_user_id',
        'first_name',
        'last_name',
        'username',
        'is_active',
        'subscribed_at',
    ];
}
