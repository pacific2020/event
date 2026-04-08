<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
   protected $table = 'events';

   protected $fillable = [
    'category',
    'event_name',
    'starting_date',
    'ending_date',
    'expected_invitation',
    'generated_at',
    'image',
    'is_active'
   ];


}
