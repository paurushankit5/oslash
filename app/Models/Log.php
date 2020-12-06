<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Log extends Model
{
    use HasFactory;
    use SoftDeletes;

    CONST LOG_TYPE_ACTION = 'Action'; 
    CONST LOG_TYPE_AUDIT = 'Audit'; 
    CONST LOG_TYPE_ACCESS = 'Access'; 
}
