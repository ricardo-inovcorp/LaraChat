<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class Message extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'content',
        'room_id',
        'receiver_id',
        'is_read',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_read' => 'boolean',
    ];
    
    /**
     * Get the user who sent the message.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get the room the message was sent to.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
    
    /**
     * Get the user who received the direct message.
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
    
    /**
     * Get all reactions to this message.
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(MessageReaction::class);
    }
    
    /**
     * Scope a query to only include direct messages.
     */
    public function scopeDirect($query)
    {
        return $query->whereNotNull('receiver_id')->whereNull('room_id');
    }
    
    /**
     * Scope a query to only include room messages.
     */
    public function scopeInRoom($query)
    {
        return $query->whereNotNull('room_id')->whereNull('receiver_id');
    }
    
    /**
     * Criptografa o conteúdo da mensagem antes de salvá-la no banco de dados.
     */
    public function setContentAttribute($value)
    {
        $this->attributes['content'] = Crypt::encrypt($value);
    }
    
    /**
     * Descriptografa o conteúdo da mensagem ao acessá-la.
     */
    public function getContentAttribute($value)
    {
        if (!$value) {
            return $value;
        }
        
        try {
            return Crypt::decrypt($value);
        } catch (\Exception $e) {
            // Se falhar a descriptografia, provavelmente não está criptografado
            // Isso garante compatibilidade com mensagens antigas
            return $value;
        }
    }
}
