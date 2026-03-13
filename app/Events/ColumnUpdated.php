<?php

namespace App\Events;

use App\Models\Column;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ColumnUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $column;

    public int $boardId;

    public int $userId;

    public function __construct(Column $column, int $boardId, int $userId)
    {
        $this->column = [
            'id' => $column->id,
            'title' => $column->title,
            'position' => $column->position,
        ];
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
            'column' => $this->column,
            'user_id' => $this->userId,
        ];
    }

    public function broadcastAs(): string
    {
        return 'ColumnUpdated';
    }
}
