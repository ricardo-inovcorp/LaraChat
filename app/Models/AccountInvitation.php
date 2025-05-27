<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'invited_by',
        'email',
        'token',
        'role',
        'expires_at',
        'accepted_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function generateToken()
    {
        return $this->token = \Str::random(32);
    }

    public function isExpired()
    {
        return $this->expires_at->isPast();
    }

    public function isAccepted()
    {
        return !is_null($this->accepted_at);
    }
}
