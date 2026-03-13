<?php

namespace App\Models;

use Database\Factories\BoardFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Board extends Model
{
    /** @use HasFactory<BoardFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'title', 'description'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function columns(): HasMany
    {
        return $this->hasMany(Column::class)->orderBy('position');
    }

    public function cards(): HasManyThrough
    {
        return $this->hasManyThrough(Card::class, Column::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class)->latest();
    }

    public function members(): HasMany
    {
        return $this->hasMany(BoardMember::class);
    }

    public function memberUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'board_members')->withPivot('role')->withTimestamps();
    }

    public function getMemberRole(int $userId): ?string
    {
        if ($this->user_id === $userId) {
            return 'owner';
        }
        $member = $this->members()->where('user_id', $userId)->first();

        return $member?->role;
    }

    public function hasMember(int $userId): bool
    {
        return $this->user_id === $userId || $this->members()->where('user_id', $userId)->exists();
    }
}
