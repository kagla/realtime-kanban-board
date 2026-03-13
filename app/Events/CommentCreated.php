<?php

namespace App\Events;

use App\Models\Comment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $comment;

    public int $boardId;

    public int $cardId;

    public function __construct(Comment $comment, int $boardId)
    {
        $this->comment = [
            'id' => $comment->id,
            'card_id' => $comment->card_id,
            'user_id' => $comment->user_id,
            'user_name' => $comment->user->name,
            'content' => $comment->content,
            'created_at' => $comment->created_at->toISOString(),
        ];
        $this->boardId = $boardId;
        $this->cardId = $comment->card_id;
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel("board.{$this->boardId}")];
    }

    public function broadcastWith(): array
    {
        return ['comment' => $this->comment];
    }

    public function broadcastAs(): string
    {
        return 'CommentCreated';
    }
}
