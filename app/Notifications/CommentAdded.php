<?php

namespace App\Notifications;

use App\Models\Board;
use App\Models\Card;
use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CommentAdded extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Comment $comment,
        private Card $card,
        private Board $board,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'comment_added',
            'board_id' => $this->board->id,
            'board_title' => $this->board->title,
            'card_id' => $this->card->id,
            'card_title' => $this->card->title,
            'commenter_name' => $this->comment->user->name,
            'comment_preview' => mb_substr($this->comment->content, 0, 50),
            'message' => "{$this->comment->user->name}님이 '{$this->card->title}' 카드에 댓글을 남겼습니다.",
        ];
    }
}
