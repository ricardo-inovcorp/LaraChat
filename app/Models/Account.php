<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function invitations()
    {
        return $this->hasMany(AccountInvitation::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($account) {
            if (empty($account->slug)) {
                $account->slug = Str::slug($account->name);
            }
        });
    }
}
