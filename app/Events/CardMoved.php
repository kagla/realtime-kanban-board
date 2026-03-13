<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CardMoved implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $cardId;

    public int $fromColumnId;

    public int $toColumnId;

    public int $position;

    public int $boardId;

    public int $userId;

    public function __construct(int $cardId, int $fromColumnId, int $toColumnId, int $position, int $boardId, int $userId)
    {
        $this->cardId = $cardId;
        $this->fromColumnId = $fromColumnId;
        $this->toColumnId = $toColumnId;
        $this->position = $position;
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
            'from_column_id' => $this->fromColumnId,
            'to_column_id' => $this->toColumnId,
            'position' => $this->position,
            'user_id' => $this->userId,
        ];
    }

    public function broadcastAs(): string
    {
        return 'CardMoved';
    }
}
