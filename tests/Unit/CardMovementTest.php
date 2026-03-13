<?php

namespace Tests\Unit;

use App\Models\Board;
use App\Models\BoardMember;
use App\Models\Card;
use App\Models\Column;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CardMovementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Board $board;

    private Column $column1;

    private Column $column2;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();

        $this->user = User::factory()->create();
        $this->board = Board::factory()->create(['user_id' => $this->user->id]);
        BoardMember::create(['board_id' => $this->board->id, 'user_id' => $this->user->id, 'role' => 'owner']);
        $this->column1 = Column::factory()->create(['board_id' => $this->board->id, 'position' => 0, 'title' => 'To Do']);
        $this->column2 = Column::factory()->create(['board_id' => $this->board->id, 'position' => 1, 'title' => 'In Progress']);
    }

    public function test_move_card_down_within_column(): void
    {
        $card1 = Card::factory()->create(['column_id' => $this->column1->id, 'position' => 0]);
        $card2 = Card::factory()->create(['column_id' => $this->column1->id, 'position' => 1]);
        $card3 = Card::factory()->create(['column_id' => $this->column1->id, 'position' => 2]);

        $this->actingAs($this->user)
            ->postJson("/api/boards/{$this->board->id}/cards/{$card1->id}/move", [
                'column_id' => $this->column1->id,
                'position' => 2,
            ])
            ->assertOk();

        $this->assertEquals(2, $card1->fresh()->position);
        $this->assertEquals(0, $card2->fresh()->position);
        $this->assertEquals(1, $card3->fresh()->position);
    }

    public function test_move_card_up_within_column(): void
    {
        $card1 = Card::factory()->create(['column_id' => $this->column1->id, 'position' => 0]);
        $card2 = Card::factory()->create(['column_id' => $this->column1->id, 'position' => 1]);
        $card3 = Card::factory()->create(['column_id' => $this->column1->id, 'position' => 2]);

        $this->actingAs($this->user)
            ->postJson("/api/boards/{$this->board->id}/cards/{$card3->id}/move", [
                'column_id' => $this->column1->id,
                'position' => 0,
            ])
            ->assertOk();

        $this->assertEquals(1, $card1->fresh()->position);
        $this->assertEquals(2, $card2->fresh()->position);
        $this->assertEquals(0, $card3->fresh()->position);
    }

    public function test_move_card_to_different_column(): void
    {
        $card1 = Card::factory()->create(['column_id' => $this->column1->id, 'position' => 0]);
        $card2 = Card::factory()->create(['column_id' => $this->column1->id, 'position' => 1]);
        $destCard = Card::factory()->create(['column_id' => $this->column2->id, 'position' => 0]);

        $this->actingAs($this->user)
            ->postJson("/api/boards/{$this->board->id}/cards/{$card1->id}/move", [
                'column_id' => $this->column2->id,
                'position' => 0,
            ])
            ->assertOk();

        $card1->refresh();
        $this->assertEquals($this->column2->id, $card1->column_id);
        $this->assertEquals(0, $card1->position);
        $this->assertEquals(1, $destCard->fresh()->position);
        $this->assertEquals(0, $card2->fresh()->position);
    }

    public function test_move_card_to_empty_column(): void
    {
        $card = Card::factory()->create(['column_id' => $this->column1->id, 'position' => 0]);

        $this->actingAs($this->user)
            ->postJson("/api/boards/{$this->board->id}/cards/{$card->id}/move", [
                'column_id' => $this->column2->id,
                'position' => 0,
            ])
            ->assertOk();

        $card->refresh();
        $this->assertEquals($this->column2->id, $card->column_id);
        $this->assertEquals(0, $card->position);
    }

    public function test_move_card_same_position_no_change(): void
    {
        $card1 = Card::factory()->create(['column_id' => $this->column1->id, 'position' => 0]);
        $card2 = Card::factory()->create(['column_id' => $this->column1->id, 'position' => 1]);

        $this->actingAs($this->user)
            ->postJson("/api/boards/{$this->board->id}/cards/{$card1->id}/move", [
                'column_id' => $this->column1->id,
                'position' => 0,
            ])
            ->assertOk();

        $this->assertEquals(0, $card1->fresh()->position);
        $this->assertEquals(1, $card2->fresh()->position);
    }

    public function test_positions_remain_sequential_after_cross_column_move(): void
    {
        $card1 = Card::factory()->create(['column_id' => $this->column1->id, 'position' => 0]);
        $card2 = Card::factory()->create(['column_id' => $this->column1->id, 'position' => 1]);
        $card3 = Card::factory()->create(['column_id' => $this->column1->id, 'position' => 2]);

        // Move middle card to column2
        $this->actingAs($this->user)
            ->postJson("/api/boards/{$this->board->id}/cards/{$card2->id}/move", [
                'column_id' => $this->column2->id,
                'position' => 0,
            ])
            ->assertOk();

        $this->assertEquals(0, $card1->fresh()->position);
        $this->assertEquals(1, $card3->fresh()->position);
        $this->assertEquals(0, $card2->fresh()->position);
        $this->assertEquals($this->column2->id, $card2->fresh()->column_id);
    }

    public function test_move_card_middle_to_end_within_column(): void
    {
        $card1 = Card::factory()->create(['column_id' => $this->column1->id, 'position' => 0]);
        $card2 = Card::factory()->create(['column_id' => $this->column1->id, 'position' => 1]);
        $card3 = Card::factory()->create(['column_id' => $this->column1->id, 'position' => 2]);
        $card4 = Card::factory()->create(['column_id' => $this->column1->id, 'position' => 3]);

        $this->actingAs($this->user)
            ->postJson("/api/boards/{$this->board->id}/cards/{$card2->id}/move", [
                'column_id' => $this->column1->id,
                'position' => 3,
            ])
            ->assertOk();

        $this->assertEquals(0, $card1->fresh()->position);
        $this->assertEquals(3, $card2->fresh()->position);
        $this->assertEquals(1, $card3->fresh()->position);
        $this->assertEquals(2, $card4->fresh()->position);
    }
}
