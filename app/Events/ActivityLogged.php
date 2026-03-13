<?php

namespace App\Events;

use App\Models\Activity;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ActivityLogged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $activity;

    public int $boardId;

    public function __construct(Activity $activity, int $boardId)
    {
        $this->activity = [
            'id' => $activity->id,
            'user_name' => $activity->user->name,
            'action' => $activity->action,
            'target_type' => $activity->target_type,
            'target_id' => $activity->target_id,
            'metadata' => $activity->metadata,
            'created_at' => $activity->created_at->toISOString(),
        ];
        $this->boardId = $boardId;
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel("board.{$this->boardId}")];
    }

    public function broadcastWith(): array
    {
        return ['activity' => $this->activity];
    }

    public function broadcastAs(): string
    {
        return 'ActivityLogged';
    }
}
