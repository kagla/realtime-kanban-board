<?php

namespace App\Notifications;

use App\Models\Board;
use App\Models\Card;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CardAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Card $card,
        private Board $board,
        private string $assignerName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'card_assigned',
            'board_id' => $this->board->id,
            'board_title' => $this->board->title,
            'card_id' => $this->card->id,
            'card_title' => $this->card->title,
            'assigner_name' => $this->assignerName,
            'message' => "{$this->assignerName}님이 '{$this->card->title}' 카드를 할당했습니다.",
        ];
    }
}
