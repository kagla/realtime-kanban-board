<?php

namespace Tests\Feature;

use App\Events\CardCreated;
use App\Events\CardDeleted;
use App\Events\CardMoved;
use App\Events\CardUpdated;
use App\Events\ColumnCreated;
use App\Events\ColumnDeleted;
use App\Events\ColumnUpdated;
use App\Events\CommentCreated;
use App\Models\Board;
use App\Models\BoardMember;
use App\Models\Card;
use App\Models\Column;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RealtimeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Board $board;

    private Column $column;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->board = Board::factory()->create(['user_id' => $this->user->id]);
        BoardMember::create(['board_id' => $this->board->id, 'user_id' => $this->user->id, 'role' => 'owner']);
        $this->column = Column::factory()->create(['board_id' => $this->board->id, 'position' => 0]);
    }

    public function test_card_created_event_is_broadcast(): void
    {
        Event::fake([CardCreated::class]);

        $this->actingAs($this->user)
            ->postJson("/api/boards/{$this->board->id}/columns/{$this->column->id}/cards", [
                'title' => 'Broadcast Test',
                'priority' => 'medium',
            ]);

        Event::assertDispatched(CardCreated::class, function ($e) {
            return $e->card['title'] === 'Broadcast Test'
                && $e->boardId === $this->board->id;
        });
    }

    public function test_card_updated_event_is_broadcast(): void
    {
        Event::fake([CardUpdated::class]);

        $card = Card::factory()->create(['column_id' => $this->column->id, 'title' => 'Old']);

        $this->actingAs($this->user)
            ->putJson("/api/boards/{$this->board->id}/cards/{$card->id}", [
                'title' => 'New Title',
            ]);

        Event::assertDispatched(CardUpdated::class);
    }

    public function test_card_deleted_event_is_broadcast(): void
    {
        Event::fake([CardDeleted::class]);

        $card = Card::factory()->create(['column_id' => $this->column->id]);

        $this->actingAs($this->user)
            ->deleteJson("/api/boards/{$this->board->id}/cards/{$card->id}");

        Event::assertDispatched(CardDeleted::class, function ($e) use ($card) {
            return $e->cardId === $card->id;
        });
    }

    public function test_card_moved_event_is_broadcast(): void
    {
        Event::fake([CardMoved::class]);

        $column2 = Column::factory()->create(['board_id' => $this->board->id, 'position' => 1]);
        $card = Card::factory()->create(['column_id' => $this->column->id, 'position' => 0]);

        $this->actingAs($this->user)
            ->postJson("/api/boards/{$this->board->id}/cards/{$card->id}/move", [
                'column_id' => $column2->id,
                'position' => 0,
            ]);

        Event::assertDispatched(CardMoved::class);
    }

    public function test_column_created_event_is_broadcast(): void
    {
        Event::fake([ColumnCreated::class]);

        $this->actingAs($this->user)
            ->postJson("/api/boards/{$this->board->id}/columns", ['title' => 'New Col']);

        Event::assertDispatched(ColumnCreated::class);
    }

    public function test_column_updated_event_is_broadcast(): void
    {
        Event::fake([ColumnUpdated::class]);

        $this->actingAs($this->user)
            ->putJson("/api/boards/{$this->board->id}/columns/{$this->column->id}", ['title' => 'Updated']);

        Event::assertDispatched(ColumnUpdated::class);
    }

    public function test_column_deleted_event_is_broadcast(): void
    {
        Event::fake([ColumnDeleted::class]);

        $this->actingAs($this->user)
            ->deleteJson("/api/boards/{$this->board->id}/columns/{$this->column->id}");

        Event::assertDispatched(ColumnDeleted::class);
    }

    public function test_comment_created_event_is_broadcast(): void
    {
        Event::fake([CommentCreated::class]);

        $card = Card::factory()->create(['column_id' => $this->column->id]);

        $this->actingAs($this->user)
            ->postJson("/api/boards/{$this->board->id}/cards/{$card->id}/comments", [
                'content' => 'Test comment',
            ]);

        Event::assertDispatched(CommentCreated::class);
    }

    public function test_card_created_event_broadcasts_on_correct_channel(): void
    {
        $card = Card::factory()->create(['column_id' => $this->column->id]);

        $event = new CardCreated($card, $this->board->id, $this->user->id);
        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertEquals("private-board.{$this->board->id}", $channels[0]->name);
    }

    public function test_card_created_event_has_correct_broadcast_name(): void
    {
        $card = Card::factory()->create(['column_id' => $this->column->id]);

        $event = new CardCreated($card, $this->board->id, $this->user->id);

        $this->assertEquals('CardCreated', $event->broadcastAs());
    }
}
