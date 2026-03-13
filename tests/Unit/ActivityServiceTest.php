<?php

namespace Tests\Unit;

use App\Events\ActivityLogged;
use App\Models\Activity;
use App\Models\Board;
use App\Models\BoardMember;
use App\Models\User;
use App\Services\ActivityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ActivityServiceTest extends TestCase
{
    use RefreshDatabase;

    private ActivityService $service;

    private User $user;

    private Board $board;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ActivityService;
        $this->user = User::factory()->create();
        $this->board = Board::factory()->create(['user_id' => $this->user->id]);
        BoardMember::create(['board_id' => $this->board->id, 'user_id' => $this->user->id, 'role' => 'owner']);
        $this->actingAs($this->user);
    }

    public function test_activity_is_saved_to_database(): void
    {
        Event::fake();

        $this->service->log($this->board, 'created', 'card', 1, ['card_title' => 'Test']);

        $this->assertDatabaseHas('activities', [
            'board_id' => $this->board->id,
            'user_id' => $this->user->id,
            'action' => 'created',
            'target_type' => 'card',
            'target_id' => 1,
        ]);
    }

    public function test_activity_stores_metadata(): void
    {
        Event::fake();

        $metadata = ['card_title' => 'Test Card', 'column_title' => 'To Do'];
        $activity = $this->service->log($this->board, 'created', 'card', 1, $metadata);

        $this->assertEquals($metadata, $activity->metadata);
    }

    public function test_activity_broadcasts_event(): void
    {
        Event::fake([ActivityLogged::class]);

        $this->service->log($this->board, 'created', 'card', 1);

        Event::assertDispatched(ActivityLogged::class, function ($e) {
            return $e->boardId === $this->board->id;
        });
    }

    public function test_activity_belongs_to_authenticated_user(): void
    {
        Event::fake();

        $activity = $this->service->log($this->board, 'updated', 'column', 5);

        $this->assertEquals($this->user->id, $activity->user_id);
    }

    public function test_activity_loads_user_relationship(): void
    {
        Event::fake();

        $activity = $this->service->log($this->board, 'deleted', 'card', 3);

        $this->assertTrue($activity->relationLoaded('user'));
        $this->assertEquals($this->user->id, $activity->user->id);
    }

    public function test_activity_with_empty_metadata(): void
    {
        Event::fake();

        $activity = $this->service->log($this->board, 'created', 'card', 1);

        $this->assertEquals([], $activity->metadata);
    }

    public function test_multiple_activities_for_same_board(): void
    {
        Event::fake();

        $this->service->log($this->board, 'created', 'card', 1);
        $this->service->log($this->board, 'updated', 'card', 1);
        $this->service->log($this->board, 'deleted', 'card', 1);

        $this->assertEquals(3, Activity::where('board_id', $this->board->id)->count());
    }
}
