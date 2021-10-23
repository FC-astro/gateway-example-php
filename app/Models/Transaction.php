<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'transaction_type',
        'transaction_status',
        'amount',
        'currency',
        'notes',
        'callback_url'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
