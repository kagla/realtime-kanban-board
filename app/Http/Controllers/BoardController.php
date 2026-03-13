<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBoardRequest;
use App\Http\Requests\UpdateBoardRequest;
use App\Models\Board;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class BoardController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        $user = auth()->user();
        $cacheKey = "user.{$user->id}.boards";

        $boards = Cache::remember($cacheKey, 300, function () use ($user) {
            return $user->allBoards()
                ->withCount('cards')
                ->withCount('columns')
                ->latest()
                ->get();
        });

        return view('boards.index', compact('boards'));
    }

    public function create(): View
    {
        return view('boards.create');
    }

    public function store(StoreBoardRequest $request): RedirectResponse
    {
        $board = $request->user()->boards()->create($request->validated());

        // Add owner as board member
        $board->members()->create([
            'user_id' => $request->user()->id,
            'role' => 'owner',
        ]);

        Cache::forget("user.{$request->user()->id}.boards");

        return redirect()->route('boards.show', $board)
            ->with('success', '보드가 생성되었습니다.');
    }

    public function show(Board $board): View
    {
        $this->authorize('view', $board);

        $board->load(['columns.cards.assignedUser', 'members.user']);

        $currentRole = $board->getMemberRole(auth()->id());

        // All users who are members of this board (for assignee dropdown)
        $users = collect([$board->user])
            ->merge($board->members->where('user_id', '!=', $board->user_id)->map->user)
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])
            ->values();

        $boardData = $board->columns->map(function ($col) {
            return [
                'id' => $col->id,
                'title' => $col->title,
                'position' => $col->position,
                'cards' => $col->cards->map(function ($card) {
                    return [
                        'id' => $card->id,
                        'title' => $card->title,
                        'description' => $card->description,
                        'priority' => $card->priority,
                        'position' => $card->position,
                        'due_date' => $card->due_date?->format('Y-m-d'),
                        'assigned_user_id' => $card->assigned_user_id,
                        'assigned_user' => $card->assignedUser
                            ? ['id' => $card->assignedUser->id, 'name' => $card->assignedUser->name]
                            : null,
                        'column_id' => $card->column_id,
                    ];
                })->values()->toArray(),
            ];
        })->values()->toArray();

        $membersData = collect([
            ['user_id' => $board->user_id, 'name' => $board->user->name, 'role' => 'owner'],
        ])->merge(
            $board->members->where('user_id', '!=', $board->user_id)->map(fn ($m) => [
                'user_id' => $m->user_id,
                'name' => $m->user->name,
                'role' => $m->role,
            ])
        )->values()->toArray();

        return view('boards.show', compact('board', 'users', 'boardData', 'currentRole', 'membersData'));
    }

    public function edit(Board $board): View
    {
        $this->authorize('update', $board);

        return view('boards.edit', compact('board'));
    }

    public function update(UpdateBoardRequest $request, Board $board): RedirectResponse
    {
        $this->authorize('update', $board);

        $board->update($request->validated());

        Cache::forget("user.{$request->user()->id}.boards");

        return redirect()->route('boards.show', $board)
            ->with('success', '보드가 수정되었습니다.');
    }

    public function destroy(Board $board): RedirectResponse
    {
        $this->authorize('delete', $board);

        $board->delete();

        Cache::forget('user.'.auth()->id().'.boards');

        return redirect()->route('boards.index')
            ->with('success', '보드가 삭제되었습니다.');
    }
}
