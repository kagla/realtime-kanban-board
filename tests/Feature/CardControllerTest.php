<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\BoardMember;
use App\Models\Card;
use App\Models\Column;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CardControllerTest extends TestCase
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

    public function test_user_can_create_card(): void
    {
        Event::fake();

        $this->actingAs($this->user)
            ->postJson("/api/boards/{$this->board->id}/columns/{$this->column->id}/cards", [
                'title' => '새 카드',
                'priority' => 'high',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', '새 카드');

        $this->assertDatabaseHas('cards', [
            'title' => '새 카드',
            'column_id' => $this->column->id,
            'priority' => 'high',
        ]);
    }

    public function test_card_creation_auto_assigns_position(): void
    {
        Event::fake();

        Card::factory()->create(['column_id' => $this->column->id, 'position' => 0]);
        Card::factory()->create(['column_id' => $this->column->id, 'position' => 1]);

        $this->actingAs($this->user)
            ->postJson("/api/boards/{$this->board->id}/columns/{$this->column->id}/cards", [
                'title' => '세 번째',
                'priority' => 'medium',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('cards', ['title' => '세 번째', 'position' => 2]);
    }

    public function test_user_can_update_card(): void
    {
        Event::fake();

        $card = Card::factory()->create(['column_id' => $this->column->id, 'title' => '원래 제목']);

        $this->actingAs($this->user)
            ->putJson("/api/boards/{$this->board->id}/cards/{$card->id}", [
                'title' => '수정된 제목',
                'priority' => 'urgent',
                'description' => '새 설명',
            ])
            ->assertOk()
            ->assertJsonPath('data.title', '수정된 제목');
    }

    public function test_user_can_delete_card(): void
    {
        Event::fake();

        $card = Card::factory()->create(['column_id' => $this->column->id]);

        $this->actingAs($this->user)
            ->deleteJson("/api/boards/{$this->board->id}/cards/{$card->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('cards', ['id' => $card->id]);
    }

    public function test_user_can_move_card_within_column(): void
    {
        Event::fake();

        $card1 = Card::factory()->create(['column_id' => $this->column->id, 'position' => 0]);
        $card2 = Card::factory()->create(['column_id' => $this->column->id, 'position' => 1]);
        $card3 = Card::factory()->create(['column_id' => $this->column->id, 'position' => 2]);

        $this->actingAs($this->user)
            ->postJson("/api/boards/{$this->board->id}/cards/{$card1->id}/move", [
                'column_id' => $this->column->id,
                'position' => 2,
            ])
            ->assertOk();

        $this->assertEquals(2, $card1->fresh()->position);
        $this->assertEquals(0, $card2->fresh()->position);
        $this->assertEquals(1, $card3->fresh()->position);
    }

    public function test_user_can_move_card_to_different_column(): void
    {
        Event::fake();

        $column2 = Column::factory()->create(['board_id' => $this->board->id, 'position' => 1]);
        $card = Card::factory()->create(['column_id' => $this->column->id, 'position' => 0]);
        Card::factory()->create(['column_id' => $column2->id, 'position' => 0]);

        $this->actingAs($this->user)
            ->postJson("/api/boards/{$this->board->id}/cards/{$card->id}/move", [
                'column_id' => $column2->id,
                'position' => 0,
            ])
            ->assertOk();

        $card->refresh();
        $this->assertEquals($column2->id, $card->column_id);
        $this->assertEquals(0, $card->position);
    }

    public function test_card_creation_requires_title(): void
    {
        $this->actingAs($this->user)
            ->postJson("/api/boards/{$this->board->id}/columns/{$this->column->id}/cards", [
                'priority' => 'medium',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    public function test_card_priority_must_be_valid(): void
    {
        $this->actingAs($this->user)
            ->postJson("/api/boards/{$this->board->id}/columns/{$this->column->id}/cards", [
                'title' => '카드',
                'priority' => 'invalid',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['priority']);
    }

    public function test_unauthenticated_user_cannot_create_card(): void
    {
        $this->postJson("/api/boards/{$this->board->id}/columns/{$this->column->id}/cards", [
            'title' => '카드',
        ])->assertUnauthorized();
    }
}
