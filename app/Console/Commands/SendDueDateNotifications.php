<?php

namespace App\Console\Commands;

use App\Models\Card;
use App\Notifications\DueDateApproaching;
use Illuminate\Console\Command;

class SendDueDateNotifications extends Command
{
    protected $signature = 'kanban:notify-due-dates';

    protected $description = '마감일 임박 카드에 대한 알림 발송';

    public function handle(): int
    {
        $tomorrow = now()->addDay()->startOfDay();

        $cards = Card::with(['assignedUser', 'column.board'])
            ->whereNotNull('assigned_user_id')
            ->whereDate('due_date', $tomorrow)
            ->get();

        $count = 0;
        foreach ($cards as $card) {
            $board = $card->column->board;
            $card->assignedUser->notify(new DueDateApproaching($card, $board->id, $board->title));
            $count++;
        }

        $this->info("마감일 임박 알림 {$count}건 발송 완료.");

        return Command::SUCCESS;
    }
}
