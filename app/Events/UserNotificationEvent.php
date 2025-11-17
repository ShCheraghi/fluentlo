<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;

class UserNotificationEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user,
        public string $title,
        public string $message,
        public array $data = []
    ) {}

    /**
     * کانالی که notification روش broadcast میشه
     */
public function broadcastOn(): Channel
{
    return new PrivateChannel('user.' . $this->user->id);
}

    /**
     * نام Event که در client استفاده میشه
     */
    public function broadcastAs(): string
    {
        return 'notification.new';
    }

    /**
     * دیتایی که به client ارسال میشه
     */
    public function broadcastWith(): array
    {
        return [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'title' => $this->title,
            'message' => $this->message,
            'data' => $this->data,
            'created_at' => now()->toISOString(),
        ];
    }
}