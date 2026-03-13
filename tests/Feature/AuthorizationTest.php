<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\BoardMember;
use App\Models\Card;
use App\Models\Column;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $editor;

    private User $viewer;

    private User $outsider;

    private Board $board;

    private Column $column;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();

        $this->owner = User::factory()->create();
        $this->editor = User::factory()->create();
        $this->viewer = User::factory()->create();
        $this->outsider = User::factory()->create();

        $this->board = Board::factory()->create(['user_id' => $this->owner->id]);
        BoardMember::create(['board_id' => $this->board->id, 'user_id' => $this->owner->id, 'role' => 'owner']);
        BoardMember::create(['board_id' => $this->board->id, 'user_id' => $this->editor->id, 'role' => 'editor']);
        BoardMember::create(['board_id' => $this->board->id, 'user_id' => $this->viewer->id, 'role' => 'viewer']);

        $this->column = Column::factory()->create(['board_id' => $this->board->id, 'position' => 0]);
    }

    // Board access
    public function test_outsider_cannot_view_board(): void
    {
        $this->actingAs($this->outsider)
            ->get(route('boards.show', $this->board))
            ->assertForbidden();
    }

    public function test_viewer_can_view_board(): void
    {
        $this->actingAs($this->viewer)
            ->get(route('boards.show', $this->board))
            ->assertOk();
    }

    // Column CRUD
    public function test_editor_can_create_column(): void
    {
        $this->actingAs($this->editor)
            ->postJson("/api/boards/{$this->board->id}/columns", ['title' => 'New Column'])
            ->assertCreated();
    }

    public function test_viewer_cannot_create_column(): void
    {
        $this->actingAs($this->viewer)
            ->postJson("/api/boards/{$this->board->id}/columns", ['title' => 'New Column'])
            ->assertForbidden();
    }

    // Card CRUD
    public function test_editor_can_create_card(): void
    {
        $this->actingAs($this->editor)
            ->postJson("/api/boards/{$this->board->id}/columns/{$this->column->id}/cards", [
                'title' => 'Card',
                'priority' => 'medium',
            ])
            ->assertCreated();
    }

    public function test_viewer_cannot_create_card(): void
    {
        $this->actingAs($this->viewer)
            ->postJson("/api/boards/{$this->board->id}/columns/{$this->column->id}/cards", [
                'title' => 'Card',
                'priority' => 'medium',
            ])
            ->assertForbidden();
    }

    public function test_viewer_cannot_delete_card(): void
    {
        $card = Card::factory()->create(['column_id' => $this->column->id]);

        $this->actingAs($this->viewer)
            ->deleteJson("/api/boards/{$this->board->id}/cards/{$card->id}")
            ->assertForbidden();
    }

    public function test_outsider_cannot_create_card(): void
    {
        $this->actingAs($this->outsider)
            ->postJson("/api/boards/{$this->board->id}/columns/{$this->column->id}/cards", [
                'title' => 'Card',
                'priority' => 'medium',
            ])
            ->assertForbidden();
    }

    // Comments
    public function test_viewer_cannot_create_comment(): void
    {
        $card = Card::factory()->create(['column_id' => $this->column->id]);

        $this->actingAs($this->viewer)
            ->postJson("/api/boards/{$this->board->id}/cards/{$card->id}/comments", [
                'content' => 'Hello',
            ])
            ->assertForbidden();
    }

    public function test_editor_can_create_comment(): void
    {
        $card = Card::factory()->create(['column_id' => $this->column->id]);

        $this->actingAs($this->editor)
            ->postJson("/api/boards/{$this->board->id}/cards/{$card->id}/comments", [
                'content' => 'Comment from editor',
            ])
            ->assertCreated();
    }

    public function test_user_can_delete_own_comment(): void
    {
        $card = Card::factory()->create(['column_id' => $this->column->id]);
        $comment = Comment::create(['card_id' => $card->id, 'user_id' => $this->editor->id, 'content' => 'Test']);

        $this->actingAs($this->editor)
            ->deleteJson("/api/boards/{$this->board->id}/cards/{$card->id}/comments/{$comment->id}")
            ->assertOk();
    }

    // Members
    public function test_only_owner_can_add_members(): void
    {
        $newUser = User::factory()->create();

        $this->actingAs($this->editor)
            ->postJson("/api/boards/{$this->board->id}/members", [
                'user_id' => $newUser->id,
                'role' => 'viewer',
            ])
            ->assertForbidden();

        $this->actingAs($this->owner)
            ->postJson("/api/boards/{$this->board->id}/members", [
                'user_id' => $newUser->id,
                'role' => 'viewer',
            ])
            ->assertCreated();
    }

    public function test_only_owner_can_delete_board(): void
    {
        $this->actingAs($this->editor)
            ->delete(route('boards.destroy', $this->board))
            ->assertForbidden();

        $this->actingAs($this->viewer)
            ->delete(route('boards.destroy', $this->board))
            ->assertForbidden();
    }

    // Activities
    public function test_viewer_can_read_activities(): void
    {
        $this->actingAs($this->viewer)
            ->getJson("/api/boards/{$this->board->id}/activities")
            ->assertOk();
    }

    public function test_outsider_cannot_read_activities(): void
    {
        $this->actingAs($this->outsider)
            ->getJson("/api/boards/{$this->board->id}/activities")
            ->assertForbidden();
    }
}
