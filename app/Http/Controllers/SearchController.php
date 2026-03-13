<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Card;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    use AuthorizesRequests;

    public function search(Request $request, Board $board): JsonResponse
    {
        $this->authorize('view', $board);

        $query = $request->get('q', '');
        if (strlen($query) < 1) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $columnIds = $board->columns()->pluck('id');

        $cards = Card::search($query)
            ->query(fn ($q) => $q->with('assignedUser:id,name', 'column:id,title')
                ->whereIn('column_id', $columnIds))
            ->get()
            ->map(fn ($card) => [
                'id' => $card->id,
                'title' => $card->title,
                'description' => $card->description,
                'priority' => $card->priority,
                'due_date' => $card->due_date?->format('Y-m-d'),
                'assigned_user' => $card->assignedUser ? ['id' => $card->assignedUser->id, 'name' => $card->assignedUser->name] : null,
                'column_id' => $card->column_id,
                'column_title' => $card->column->title,
            ]);

        return response()->json(['success' => true, 'data' => $cards]);
    }

    public function filter(Request $request, Board $board): JsonResponse
    {
        $this->authorize('view', $board);

        $columnIds = $board->columns()->pluck('id');
        $query = Card::whereIn('column_id', $columnIds)->with('assignedUser:id,name', 'column:id,title');

        if ($request->filled('assigned_user_id')) {
            $query->where('assigned_user_id', $request->assigned_user_id);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('due_filter')) {
            $today = now()->startOfDay();
            switch ($request->due_filter) {
                case 'today':
                    $query->whereDate('due_date', $today);
                    break;
                case 'this_week':
                    $query->whereBetween('due_date', [$today, now()->endOfWeek()]);
                    break;
                case 'overdue':
                    $query->where('due_date', '<', $today)->whereNotNull('due_date');
                    break;
            }
        }

        $cards = $query->orderBy('position')->get()->map(fn ($card) => [
            'id' => $card->id,
            'title' => $card->title,
            'description' => $card->description,
            'priority' => $card->priority,
            'due_date' => $card->due_date?->format('Y-m-d'),
            'assigned_user' => $card->assignedUser ? ['id' => $card->assignedUser->id, 'name' => $card->assignedUser->name] : null,
            'column_id' => $card->column_id,
            'column_title' => $card->column->title,
        ]);

        return response()->json(['success' => true, 'data' => $cards]);
    }
}
