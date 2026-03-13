<?php

namespace App\Services;

use App\Events\ActivityLogged;
use App\Models\Activity;
use App\Models\Board;

class ActivityService
{
    public function log(Board $board, string $action, string $targetType, int $targetId, array $metadata = []): Activity
    {
        $activity = Activity::create([
            'board_id' => $board->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'metadata' => $metadata,
        ]);

        $activity->load('user');

        broadcast(new ActivityLogged($activity, $board->id))->toOthers();

        return $activity;
    }
}
