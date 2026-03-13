<?php

namespace App\Http\Controllers;

use App\Events\ColumnCreated;
use App\Events\ColumnDeleted;
use App\Events\ColumnUpdated;
use App\Http\Requests\ReorderColumnRequest;
use App\Http\Requests\StoreColumnRequest;
use App\Http\Requests\UpdateColumnRequest;
use App\Models\Board;
use App\Models\Column;
use App\Services\ActivityService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class ColumnController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private ActivityService $activityService) {}

    public function store(StoreColumnRequest $request, Board $board): JsonResponse
    {
        $this->authorize('update', $board);

        $maxPosition = $board->columns()->max('position') ?? -1;

        $column = $board->columns()->create([
            'title' => $request->validated('title'),
            'position' => $maxPosition + 1,
        ]);

        $this->activityService->log($board, 'created', 'column', $column->id, [
            'column_title' => $column->title,
        ]);

        broadcast(new ColumnCreated($column, $board->id, $request->user()->id))->toOthers();

        return response()->json([
            'success' => true,
            'message' => '컬럼이 생성되었습니다.',
            'data' => $column,
        ], 201);
    }

    public function update(UpdateColumnRequest $request, Board $board, Column $column): JsonResponse
    {
        $this->authorize('update', $board);

        $oldTitle = $column->title;
        $column->update($request->validated());

        $this->activityService->log($board, 'updated', 'column', $column->id, [
            'column_title' => $column->title,
            'old_title' => $oldTitle,
        ]);

        broadcast(new ColumnUpdated($column, $board->id, $request->user()->id))->toOthers();

        return response()->json([
            'success' => true,
            'message' => '컬럼이 수정되었습니다.',
            'data' => $column,
        ]);
    }

    public function destroy(Board $board, Column $column): JsonResponse
    {
        $this->authorize('update', $board);

        $columnId = $column->id;
        $columnTitle = $column->title;
        $column->delete();

        $this->activityService->log($board, 'deleted', 'column', $columnId, [
            'column_title' => $columnTitle,
        ]);

        broadcast(new ColumnDeleted($columnId, $board->id, auth()->id()))->toOthers();

        return response()->json([
            'success' => true,
            'message' => '컬럼이 삭제되었습니다.',
        ]);
    }

    public function reorder(ReorderColumnRequest $request, Board $board, Column $column): JsonResponse
    {
        $this->authorize('update', $board);

        $newPosition = $request->validated('position');
        $oldPosition = $column->position;

        if ($newPosition > $oldPosition) {
            $board->columns()
                ->where('position', '>', $oldPosition)
                ->where('position', '<=', $newPosition)
                ->decrement('position');
        } elseif ($newPosition < $oldPosition) {
            $board->columns()
                ->where('position', '>=', $newPosition)
                ->where('position', '<', $oldPosition)
                ->increment('position');
        }

        $column->update(['position' => $newPosition]);

        $this->activityService->log($board, 'reordered', 'column', $column->id, [
            'column_title' => $column->title,
            'from_position' => $oldPosition,
            'to_position' => $newPosition,
        ]);

        broadcast(new ColumnUpdated($column->fresh(), $board->id, $request->user()->id))->toOthers();

        return response()->json([
            'success' => true,
            'message' => '컬럼 순서가 변경되었습니다.',
        ]);
    }
}
