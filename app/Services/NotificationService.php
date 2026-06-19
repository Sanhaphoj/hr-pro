<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    /**
     * Create an in-app notification for a user.
     */
    public function notify(User|int $user, string $title, string $message, string $type = 'info', ?string $link = null): Notification
    {
        return Notification::create([
            'user_id' => $user instanceof User ? $user->id : $user,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
        ]);
    }
}
