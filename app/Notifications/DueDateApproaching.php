<?php

namespace App\Notifications;

use App\Models\Card;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class DueDateApproaching extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Card $card,
        private int $boardId,
        private string $boardTitle,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'due_date_approaching',
            'board_id' => $this->boardId,
            'board_title' => $this->boardTitle,
            'card_id' => $this->card->id,
            'card_title' => $this->card->title,
            'due_date' => $this->card->due_date->format('Y-m-d'),
            'message' => "'{$this->card->title}' 카드의 마감일이 내일입니다.",
        ];
    }
}
