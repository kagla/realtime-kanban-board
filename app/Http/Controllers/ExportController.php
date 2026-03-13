<?php

namespace App\Http\Controllers;

use App\Models\Board;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ExportController extends Controller
{
    use AuthorizesRequests;

    public function json(Board $board): JsonResponse
    {
        $this->authorize('view', $board);

        $board->load(['columns.cards.assignedUser', 'columns.cards.comments.user']);

        $data = [
            'board' => [
                'title' => $board->title,
                'description' => $board->description,
                'exported_at' => now()->toIso8601String(),
            ],
            'columns' => $board->columns->sortBy('position')->values()->map(fn ($col) => [
                'title' => $col->title,
                'position' => $col->position,
                'cards' => $col->cards->sortBy('position')->values()->map(fn ($card) => [
                    'title' => $card->title,
                    'description' => $card->description,
                    'priority' => $card->priority,
                    'due_date' => $card->due_date?->format('Y-m-d'),
                    'assigned_to' => $card->assignedUser?->name,
                    'comments' => $card->comments->map(fn ($c) => [
                        'author' => $c->user->name,
                        'content' => $c->content,
                        'created_at' => $c->created_at->toIso8601String(),
                    ])->values(),
                ]),
            ]),
        ];

        return response()->json($data, 200, [
            'Content-Disposition' => 'attachment; filename="'.$board->title.'.json"',
        ]);
    }

    public function markdown(Board $board): Response
    {
        $this->authorize('view', $board);

        $board->load(['columns.cards.assignedUser']);

        $md = "# {$board->title}\n\n";
        if ($board->description) {
            $md .= "{$board->description}\n\n";
        }
        $md .= '> Exported: '.now()->format('Y-m-d H:i')."\n\n---\n\n";

        foreach ($board->columns->sortBy('position') as $col) {
            $md .= "## {$col->title}\n\n";
            $cards = $col->cards->sortBy('position');

            if ($cards->isEmpty()) {
                $md .= "*No cards*\n\n";

                continue;
            }

            foreach ($cards as $card) {
                $priority = strtoupper($card->priority);
                $md .= "- **[{$priority}]** {$card->title}";
                if ($card->assignedUser) {
                    $md .= " (@{$card->assignedUser->name})";
                }
                if ($card->due_date) {
                    $md .= " | Due: {$card->due_date->format('Y-m-d')}";
                }
                $md .= "\n";
                if ($card->description) {
                    $md .= "  > {$card->description}\n";
                }
            }

            $md .= "\n";
        }

        return response($md, 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$board->title.'.md"',
        ]);
    }
}
