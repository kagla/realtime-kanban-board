<?php

namespace App\Http\Controllers;

use App\Events\CommentCreated;
use App\Models\Board;
use App\Models\Card;
use App\Models\Comment;
use App\Notifications\CommentAdded;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    use AuthorizesRequests;

    public function index(Board $board, Card $card): JsonResponse
    {
        $this->authorize('view', $board);

        $comments = $card->comments()->with('user:id,name')->get()->map(fn ($c) => [
            'id' => $c->id,
            'card_id' => $c->card_id,
            'user_id' => $c->user_id,
            'user_name' => $c->user->name,
            'content' => $c->content,
            'created_at' => $c->created_at->toISOString(),
        ]);

        return response()->json(['success' => true, 'data' => $comments]);
    }

    public function store(Request $request, Board $board, Card $card): JsonResponse
    {
        $this->authorize('view', $board);

        $role = $board->getMemberRole(auth()->id());
        if ($role === 'viewer') {
            return response()->json(['success' => false, 'message' => '댓글 작성 권한이 없습니다.'], 403);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ], [
            'content.required' => '댓글 내용을 입력해주세요.',
            'content.max' => '댓글은 1000자를 초과할 수 없습니다.',
        ]);

        $comment = $card->comments()->create([
            'user_id' => auth()->id(),
            'content' => $validated['content'],
        ]);

        $comment->load('user:id,name');

        broadcast(new CommentCreated($comment, $board->id))->toOthers();

        // Notify card assignee and card owner if different from commenter
        if ($card->assigned_user_id && $card->assigned_user_id !== auth()->id()) {
            $card->assignedUser->notify(new CommentAdded($comment, $card, $board));
        }

        return response()->json([
            'success' => true,
            'message' => '댓글이 작성되었습니다.',
            'data' => [
                'id' => $comment->id,
                'card_id' => $comment->card_id,
                'user_id' => $comment->user_id,
                'user_name' => $comment->user->name,
                'content' => $comment->content,
                'created_at' => $comment->created_at->toISOString(),
            ],
        ], 201);
    }

    public function destroy(Board $board, Card $card, Comment $comment): JsonResponse
    {
        $this->authorize('view', $board);

        if ($comment->user_id !== auth()->id()) {
            $role = $board->getMemberRole(auth()->id());
            if (! in_array($role, ['owner', 'editor'])) {
                return response()->json(['success' => false, 'message' => '삭제 권한이 없습니다.'], 403);
            }
        }

        $comment->delete();

        return response()->json(['success' => true, 'message' => '댓글이 삭제되었습니다.']);
    }
}
