<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function boards(): HasMany
    {
        return $this->hasMany(Board::class);
    }

    public function memberBoards()
    {
        return $this->belongsToMany(Board::class, 'board_members')->withPivot('role')->withTimestamps();
    }

    public function allBoards()
    {
        $ownBoardIds = $this->boards()->pluck('id');
        $memberBoardIds = $this->memberBoards()->pluck('boards.id');

        return Board::whereIn('id', $ownBoardIds->merge($memberBoardIds)->unique());
    }

    public function assignedCards(): HasMany
    {
        return $this->hasMany(Card::class, 'assigned_user_id');
    }
}
