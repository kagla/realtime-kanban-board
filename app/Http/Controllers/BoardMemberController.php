<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\BoardMember;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BoardMemberController extends Controller
{
    use AuthorizesRequests;

    public function index(Board $board): JsonResponse
    {
        $this->authorize('view', $board);

        $owner = $board->user;
        $members = $board->members()->with('user:id,name,email')->get()->map(fn ($m) => [
            'id' => $m->id,
            'user_id' => $m->user_id,
            'name' => $m->user->name,
            'email' => $m->user->email,
            'role' => $m->role,
        ]);

        $ownerData = [
            'id' => null,
            'user_id' => $owner->id,
            'name' => $owner->name,
            'email' => $owner->email,
            'role' => 'owner',
        ];

        return response()->json([
            'success' => true,
            'data' => collect([$ownerData])->merge($members),
        ]);
    }

    public function searchUsers(Request $request, Board $board): JsonResponse
    {
        $this->authorize('update', $board);

        $query = $request->get('q', '');
        if (strlen($query) < 2) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $existingIds = $board->members()->pluck('user_id')->push($board->user_id);

        $users = User::where(function ($q) use ($query) {
            $q->where('email', 'like', "%{$query}%")
                ->orWhere('name', 'like', "%{$query}%");
        })
            ->whereNotIn('id', $existingIds)
            ->select('id', 'name', 'email')
            ->limit(10)
            ->get();

        return response()->json(['success' => true, 'data' => $users]);
    }

    public function store(Request $request, Board $board): JsonResponse
    {
        $this->authorize('delete', $board);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:editor,viewer',
        ], [
            'user_id.required' => '사용자를 선택해주세요.',
            'user_id.exists' => '존재하지 않는 사용자입니다.',
            'role.required' => '역할을 선택해주세요.',
            'role.in' => '유효하지 않은 역할입니다.',
        ]);

        if ($board->hasMember($validated['user_id'])) {
            return response()->json(['success' => false, 'message' => '이미 보드 멤버입니다.'], 422);
        }

        $member = $board->members()->create($validated);
        $member->load('user:id,name,email');

        return response()->json([
            'success' => true,
            'message' => '멤버가 추가되었습니다.',
            'data' => [
                'id' => $member->id,
                'user_id' => $member->user_id,
                'name' => $member->user->name,
                'email' => $member->user->email,
                'role' => $member->role,
            ],
        ], 201);
    }

    public function update(Request $request, Board $board, BoardMember $member): JsonResponse
    {
        $this->authorize('update', $board);

        $validated = $request->validate([
            'role' => 'required|in:editor,viewer',
        ]);

        $member->update($validated);

        return response()->json(['success' => true, 'message' => '역할이 변경되었습니다.']);
    }

    public function destroy(Board $board, BoardMember $member): JsonResponse
    {
        $this->authorize('update', $board);

        $member->delete();

        return response()->json(['success' => true, 'message' => '멤버가 제거되었습니다.']);
    }
}
