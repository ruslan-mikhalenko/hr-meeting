<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'original_email', 'original_password']; // Поля, которые могут быть массово присвоены

    /**
     * Связь: Клиент принадлежит Пользователю.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
