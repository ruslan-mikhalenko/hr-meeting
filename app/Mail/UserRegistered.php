<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserRegistered extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $password;

    /**
     * Создайте новый экземпляр сообщения.
     *
     * @param User $user
     * @param string $password
     */
    public function __construct(User $user, $password)
    {
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * Построить сообщение.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.user_registered')
            ->with([
                'name' => $this->user->name,
                'password' => $this->password,
            ])
            ->subject('Welcome to Our Application');
    }
}
