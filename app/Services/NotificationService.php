<?php

namespace App\Services;

use App\Mail\NotificationMail;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Create an in-app notification and fan it out to the user's email and
     * (optionally) LINE. Email uses the configured mailer (the `log` mailer in
     * local/demo, so messages land in storage/logs). External delivery never
     * breaks the caller — failures are caught and logged.
     */
    public function notify(User|int $user, string $title, string $message, string $type = 'info', ?string $link = null): Notification
    {
        $userId = $user instanceof User ? $user->id : $user;

        $notification = Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
        ]);

        $recipient = $user instanceof User ? $user : User::find($userId);

        if ($recipient) {
            $this->sendEmail($recipient, $title, $message, $link);
            $this->sendLine($title, $message);
        }

        return $notification;
    }

    /**
     * Email channel — sent synchronously via the configured mailer.
     */
    protected function sendEmail(User $user, string $title, string $message, ?string $link): void
    {
        if (! $user->email) {
            return;
        }

        try {
            $url = $link ? url($link) : null;
            Mail::to($user->email)->send(new NotificationMail($title, $message, $url));
        } catch (\Throwable $e) {
            Log::warning('Notification email failed: '.$e->getMessage());
        }
    }

    /**
     * LINE channel — no-op unless a notify token is configured
     * (config services.line.token / env LINE_NOTIFY_TOKEN).
     */
    protected function sendLine(string $title, string $message): void
    {
        $token = config('services.line.token');
        if (! $token) {
            return;
        }

        try {
            Http::asForm()
                ->withToken($token)
                ->post('https://notify-api.line.me/api/notify', [
                    'message' => "\n{$title}\n{$message}",
                ]);
        } catch (\Throwable $e) {
            Log::warning('LINE notify failed: '.$e->getMessage());
        }
    }
}
