<?php

namespace App\Http\Controllers;

use App\Events\CardCreated;
use App\Events\CardDeleted;
use App\Events\CardMoved;
use App\Events\CardUpdated;
use App\Http\Requests\MoveCardRequest;
use App\Http\Requests\StoreCardRequest;
use App\Http\Requests\UpdateCardRequest;
use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Notifications\CardAssigned;
use App\Services\ActivityService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class CardController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private ActivityService $activityService) {}

    public function store(StoreCardRequest $request, Board $board, Column $column): JsonResponse
    {
        $this->authorize('update', $board);

        $maxPosition = $column->cards()->max('position') ?? -1;

        $card = $column->cards()->create(array_merge(
            $request->validated(),
            ['position' => $maxPosition + 1],
        ));

        $card->load('assignedUser');

        $this->activityService->log($board, 'created', 'card', $card->id, [
            'card_title' => $card->title,
            'column_title' => $column->title,
        ]);

        broadcast(new CardCreated($card, $board->id, $request->user()->id))->toOthers();

        return response()->json([
            'success' => true,
            'message' => '카드가 생성되었습니다.',
            'data' => $card,
        ], 201);
    }

    public function update(UpdateCardRequest $request, Board $board, Card $card): JsonResponse
    {
        $this->authorize('update', $board);

        $oldTitle = $card->title;
        $oldAssignee = $card->assigned_user_id;
        $card->update($request->validated());
        $card->load('assignedUser');

        // Notify new assignee
        if ($card->assigned_user_id && $card->assigned_user_id !== $oldAssignee && $card->assigned_user_id !== auth()->id()) {
            $card->assignedUser->notify(new CardAssigned($card, $board, auth()->user()->name));
        }

        $this->activityService->log($board, 'updated', 'card', $card->id, [
            'card_title' => $card->title,
            'old_title' => $oldTitle,
        ]);

        broadcast(new CardUpdated($card, $board->id, $request->user()->id))->toOthers();

        return response()->json([
            'success' => true,
            'message' => '카드가 수정되었습니다.',
            'data' => $card,
        ]);
    }

    public function destroy(Board $board, Card $card): JsonResponse
    {
        $this->authorize('update', $board);

        $cardId = $card->id;
        $columnId = $card->column_id;
        $cardTitle = $card->title;
        $card->delete();

        $this->activityService->log($board, 'deleted', 'card', $cardId, [
            'card_title' => $cardTitle,
        ]);

        broadcast(new CardDeleted($cardId, $columnId, $board->id, auth()->id()))->toOthers();

        return response()->json([
            'success' => true,
            'message' => '카드가 삭제되었습니다.',
        ]);
    }

    public function move(MoveCardRequest $request, Board $board, Card $card): JsonResponse
    {
        $this->authorize('update', $board);

        $validated = $request->validated();
        $newColumnId = $validated['column_id'];
        $newPosition = $validated['position'];
        $oldColumnId = $card->column_id;
        $oldPosition = $card->position;

        $fromColumn = Column::find($oldColumnId);
        $toColumn = Column::find($newColumnId);

        if ($oldColumnId == $newColumnId) {
            if ($newPosition > $oldPosition) {
                $toColumn->cards()
                    ->where('position', '>', $oldPosition)
                    ->where('position', '<=', $newPosition)
                    ->decrement('position');
            } elseif ($newPosition < $oldPosition) {
                $toColumn->cards()
                    ->where('position', '>=', $newPosition)
                    ->where('position', '<', $oldPosition)
                    ->increment('position');
            }
        } else {
            $fromColumn->cards()
                ->where('position', '>', $oldPosition)
                ->decrement('position');

            $toColumn->cards()
                ->where('position', '>=', $newPosition)
                ->increment('position');
        }

        $card->update([
            'column_id' => $newColumnId,
            'position' => $newPosition,
        ]);

        $this->activityService->log($board, 'moved', 'card', $card->id, [
            'card_title' => $card->title,
            'from_column' => $fromColumn->title,
            'to_column' => $toColumn->title,
        ]);

        broadcast(new CardMoved(
            $card->id, $oldColumnId, $newColumnId, $newPosition,
            $board->id, $request->user()->id
        ))->toOthers();

        return response()->json([
            'success' => true,
            'message' => '카드가 이동되었습니다.',
        ]);
    }
}
