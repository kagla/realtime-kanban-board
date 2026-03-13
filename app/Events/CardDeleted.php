<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CardDeleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $cardId;

    public int $columnId;

    public int $boardId;

    public int $userId;

    public function __construct(int $cardId, int $columnId, int $boardId, int $userId)
    {
        $this->cardId = $cardId;
        $this->columnId = $columnId;
        $this->boardId = $boardId;
        $this->userId = $userId;
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel("board.{$this->boardId}")];
    }

    public function broadcastWith(): array
    {
        return [
            'card_id' => $this->cardId,
            'column_id' => $this->columnId,
            'user_id' => $this->userId,
        ];
    }

    public function broadcastAs(): string
    {
        return 'CardDeleted';
    }
}
