<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $password;
    public $loginUrl;

    public function __construct(User $user, $password)
    {
        $this->user = $user;
        $this->password = $password;
        // Fetch from .env, fallback to localhost if missing
        $this->loginUrl = config('app.frontend_url', env('FRONTEND_URL')) . '/login';
    }

    public function build()
    {
        return $this->subject('Account Created - Event Staff')
                    ->html("
                        <h1>Welcome, {$this->user->fullname}!</h1>
                        <p>You have been registered to work as a <strong>{$this->user->position}</strong> in the upcoming event.</p>
                        <p><strong>Your Login Credentials:</strong></p>
                        <ul>
                            <li>Email: {$this->user->email}</li>
                            <li>Password: {$this->password}</li>
                        </ul>
                        <p>You can login here: <a href='{$this->loginUrl}'>{$this->loginUrl}</a></p>
                    ");
    }
}
