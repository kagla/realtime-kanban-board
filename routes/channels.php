<?php

use App\Models\Board;
use Illuminate\Support\Facades\Broadcast;

// Presence channel for board — returns user info for online users list
Broadcast::channel('board.{boardId}', function ($user, $boardId) {
    $board = Board::find($boardId);

    if ($board && $board->hasMember($user->id)) {
        return ['id' => $user->id, 'name' => $user->name];
    }

    return false;
});
