<?php

namespace App\Http\Controllers;

use App\Models\Board;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request, Board $board): JsonResponse
    {
        $this->authorize('view', $board);

        $query = $board->activities()->with('user')->latest();

        if ($request->has('filter') && in_array($request->filter, ['card', 'column'])) {
            $query->where('target_type', $request->filter);
        }

        $activities = $query->take(20)->get()->map(function ($activity) {
            return [
                'id' => $activity->id,
                'user_name' => $activity->user->name,
                'action' => $activity->action,
                'target_type' => $activity->target_type,
                'target_id' => $activity->target_id,
                'metadata' => $activity->metadata,
                'created_at' => $activity->created_at->toISOString(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $activities,
        ]);
    }
}
