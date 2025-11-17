<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Str;

class GeneralNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    public string $publicId;
    public ?int $userId;

    public function __construct(
        public string $title,
        public string $message,
        public array $data = [],
        ?int $userId = null
    ) {
        $this->publicId = (string) Str::ulid();
        $this->userId = $userId;
    }

    public function via(object $notifiable): array
    {
        if (!$this->userId) $this->userId = $notifiable->id;
        return ['database','broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'public_id'  => $this->publicId,
            'title'      => $this->title,
            'message'    => $this->message,
            'data'       => $this->data,
            'created_at' => now()->toISOString(),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'public_id'  => $this->publicId,
            'title'      => $this->title,
            'message'    => $this->message,
            'data'       => $this->data,
            'created_at' => now()->toISOString(),
        ]);
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.' . $this->userId)];
    }

    public function broadcastAs(): string
    {
        return 'notification.new';
    }
}
