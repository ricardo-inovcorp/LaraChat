<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'created_by',
        'is_private',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_private' => 'boolean',
    ];
    
    /**
     * Get the user who created the room.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    /**
     * Get all members of the room.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_rooms')
                    ->withPivot('is_admin')
                    ->withTimestamps();
    }
    
    /**
     * Get all messages in the room.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
    
    /**
     * Get all join requests for the room.
     */
    public function joinRequests(): HasMany
    {
        return $this->hasMany(RoomJoinRequest::class);
    }
}
