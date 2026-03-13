<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Board;
use App\Models\BoardMember;
use App\Models\Card;
use App\Models\Column;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 테스트 유저 생성
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $users = User::factory(4)->create([
            'password' => bcrypt('password'),
        ]);

        $allUsers = $users->push($admin);

        // 보드 생성
        $boardConfigs = [
            ['title' => '프로젝트 관리', 'description' => '메인 프로젝트 칸반 보드'],
            ['title' => '스프린트 #12', 'description' => '현재 스프린트 작업 관리'],
            ['title' => '버그 트래커', 'description' => '버그 수정 및 이슈 추적'],
        ];

        foreach ($boardConfigs as $index => $config) {
            $owner = $allUsers[$index % $allUsers->count()];
            $board = Board::factory()->create([
                'user_id' => $owner->id,
                'title' => $config['title'],
                'description' => $config['description'],
            ]);

            // Add owner as board member
            BoardMember::create([
                'board_id' => $board->id,
                'user_id' => $owner->id,
                'role' => 'owner',
            ]);

            // Add some other users as members
            $otherUsers = $allUsers->where('id', '!=', $owner->id)->random(min(2, $allUsers->count() - 1));
            foreach ($otherUsers as $u) {
                BoardMember::create([
                    'board_id' => $board->id,
                    'user_id' => $u->id,
                    'role' => fake()->randomElement(['editor', 'viewer']),
                ]);
            }

            // 기본 컬럼 생성
            $columnTitles = ['할 일', '진행 중', '검토 중', '완료'];
            $columns = collect();

            foreach ($columnTitles as $pos => $title) {
                $columns->push(Column::factory()->create([
                    'board_id' => $board->id,
                    'title' => $title,
                    'position' => $pos,
                ]));
            }

            // 각 컬럼에 카드 생성
            foreach ($columns as $column) {
                $cardCount = fake()->numberBetween(2, 5);
                for ($i = 0; $i < $cardCount; $i++) {
                    $card = Card::factory()->create([
                        'column_id' => $column->id,
                        'assigned_user_id' => $allUsers->random()->id,
                        'position' => $i,
                    ]);

                    // 댓글 생성
                    $commentCount = fake()->numberBetween(0, 3);
                    for ($j = 0; $j < $commentCount; $j++) {
                        Comment::create([
                            'card_id' => $card->id,
                            'user_id' => $allUsers->random()->id,
                            'content' => fake()->sentence(fake()->numberBetween(3, 15)),
                        ]);
                    }

                    // 활동 로그 생성
                    Activity::factory()->create([
                        'board_id' => $board->id,
                        'user_id' => $owner->id,
                        'action' => 'created',
                        'target_type' => 'card',
                        'target_id' => $card->id,
                        'metadata' => ['card_title' => $card->title],
                    ]);
                }
            }

            // 추가 활동 로그
            Activity::factory(3)->create([
                'board_id' => $board->id,
                'user_id' => $allUsers->random()->id,
            ]);
        }
    }
}
