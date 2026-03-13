<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\BoardMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoardControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_guest_cannot_access_boards(): void
    {
        $this->get(route('boards.index'))->assertRedirect(route('login'));
    }

    public function test_user_can_view_board_list(): void
    {
        $board = Board::factory()->create(['user_id' => $this->user->id]);
        BoardMember::create(['board_id' => $board->id, 'user_id' => $this->user->id, 'role' => 'owner']);

        $this->actingAs($this->user)
            ->get(route('boards.index'))
            ->assertOk()
            ->assertSee($board->title);
    }

    public function test_user_can_create_board(): void
    {
        $this->actingAs($this->user)
            ->post(route('boards.store'), [
                'title' => '새 보드',
                'description' => '테스트 설명',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('boards', [
            'title' => '새 보드',
            'user_id' => $this->user->id,
        ]);

        $this->assertDatabaseHas('board_members', [
            'user_id' => $this->user->id,
            'role' => 'owner',
        ]);
    }

    public function test_user_can_view_own_board(): void
    {
        $board = Board::factory()->create(['user_id' => $this->user->id]);
        BoardMember::create(['board_id' => $board->id, 'user_id' => $this->user->id, 'role' => 'owner']);

        $this->actingAs($this->user)
            ->get(route('boards.show', $board))
            ->assertOk()
            ->assertSee($board->title);
    }

    public function test_user_cannot_view_others_board_without_membership(): void
    {
        $other = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $other->id]);

        $this->actingAs($this->user)
            ->get(route('boards.show', $board))
            ->assertForbidden();
    }

    public function test_member_can_view_board(): void
    {
        $owner = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $owner->id]);
        BoardMember::create(['board_id' => $board->id, 'user_id' => $this->user->id, 'role' => 'viewer']);

        $this->actingAs($this->user)
            ->get(route('boards.show', $board))
            ->assertOk();
    }

    public function test_user_can_update_own_board(): void
    {
        $board = Board::factory()->create(['user_id' => $this->user->id]);
        BoardMember::create(['board_id' => $board->id, 'user_id' => $this->user->id, 'role' => 'owner']);

        $this->actingAs($this->user)
            ->put(route('boards.update', $board), [
                'title' => '수정된 제목',
                'description' => '수정된 설명',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('boards', [
            'id' => $board->id,
            'title' => '수정된 제목',
        ]);
    }

    public function test_user_can_delete_own_board(): void
    {
        $board = Board::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->delete(route('boards.destroy', $board))
            ->assertRedirect(route('boards.index'));

        $this->assertDatabaseMissing('boards', ['id' => $board->id]);
    }

    public function test_editor_cannot_delete_board(): void
    {
        $owner = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $owner->id]);
        BoardMember::create(['board_id' => $board->id, 'user_id' => $this->user->id, 'role' => 'editor']);

        $this->actingAs($this->user)
            ->delete(route('boards.destroy', $board))
            ->assertForbidden();
    }

    public function test_board_creation_validates_required_fields(): void
    {
        $this->actingAs($this->user)
            ->post(route('boards.store'), [])
            ->assertSessionHasErrors(['title']);
    }

    public function test_board_list_shows_member_boards(): void
    {
        $owner = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $owner->id, 'title' => '공유 보드']);
        BoardMember::create(['board_id' => $board->id, 'user_id' => $owner->id, 'role' => 'owner']);
        BoardMember::create(['board_id' => $board->id, 'user_id' => $this->user->id, 'role' => 'editor']);

        $this->actingAs($this->user)
            ->get(route('boards.index'))
            ->assertOk()
            ->assertSee('공유 보드');
    }
}
