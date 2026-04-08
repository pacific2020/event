<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable; // Change this
use Laravel\Sanctum\HasApiTokens; // Add this

class GraduationList extends Authenticatable // Extend Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $table = 'graduation_list';

    protected $fillable =[
        'reg_no',
        'college_name',
        'first_name',
        'last_name',
        'last_name',
        'status',
        'scanned_number'
    ];
}
