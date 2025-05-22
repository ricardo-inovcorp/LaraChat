<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
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
        'avatar',
        'permissions',
        'status',
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
    
    /**
     * Verificar se o usuário é um administrador.
     */
    public function isAdmin(): bool
    {
        return $this->permissions === 'admin';
    }
    
    /**
     * Verificar se o usuário está ativo.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
    
    /**
     * Get all rooms created by the user.
     */
    public function createdRooms(): HasMany
    {
        return $this->hasMany(Room::class, 'created_by');
    }
    
    /**
     * Get all rooms the user is a member of.
     */
    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'user_rooms')
                    ->withPivot('is_admin')
                    ->withTimestamps();
    }
    
    /**
     * Get all messages sent by the user.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'user_id');
    }
    
    /**
     * Get all direct messages received by the user.
     */
    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }
    
    /**
     * Get all join requests made by the user.
     */
    public function joinRequests(): HasMany
    {
        return $this->hasMany(RoomJoinRequest::class);
    }

    /**
     * Check if the user is online.
     * A user is considered online if they have a session active in the last 5 minutes.
     */
    public function isOnline(): bool
    {
        // Se o usuário não estiver ativo no sistema, não pode estar online
        if ($this->status !== 'active') {
            return false;
        }
        
        // Verificar se o usuário tem uma sessão ativa nos últimos 5 minutos
        $lastActivity = DB::table('sessions')
            ->where('user_id', $this->id)
            ->max('last_activity');
        
        if (!$lastActivity) {
            return false;
        }
        
        // Converter timestamp para Carbon e verificar se está dentro dos últimos 5 minutos
        $lastActiveTime = Carbon::createFromTimestamp($lastActivity);
        return $lastActiveTime->isAfter(now()->subMinutes(5));
    }
}
