<?php

namespace App\Events;

use App\Models\Card;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CardUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $card;

    public int $boardId;

    public int $userId;

    public function __construct(Card $card, int $boardId, int $userId)
    {
        $card->load('assignedUser');
        $this->card = [
            'id' => $card->id,
            'title' => $card->title,
            'description' => $card->description,
            'priority' => $card->priority,
            'position' => $card->position,
            'due_date' => $card->due_date?->format('Y-m-d'),
            'assigned_user_id' => $card->assigned_user_id,
            'assigned_user' => $card->assignedUser ? ['id' => $card->assignedUser->id, 'name' => $card->assignedUser->name] : null,
            'column_id' => $card->column_id,
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
            'card' => $this->card,
            'user_id' => $this->userId,
        ];
    }

    public function broadcastAs(): string
    {
        return 'CardUpdated';
    }
}
