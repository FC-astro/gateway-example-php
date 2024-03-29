<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionStatus extends Model
{
    const CANCELED = 0;
    const PENDING = 1;
    const COMPLETED = 2;
    const EXPIRED = 3;
    const NEW = 4;
}
