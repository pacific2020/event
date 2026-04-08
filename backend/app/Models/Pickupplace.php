<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pickupplace extends Model
{
    use HasFactory;

    // Use the exact table name from your migration
    protected $table = 'pickupplace';

    // Ensure all columns are in the fillable array
    protected $fillable = [
        'college_id',
        'reg_no',
        'status',
    ]; // <--- Check that this semicolon is present
}
