<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;

class College extends Model
{
      // Tell Eloquent to use `college` table instead of `colleges`
    protected $table = 'college';

protected $fillable = [
    'polytechnic',
    'short_name'

];

 // ✅ correct relationship
    public function users(): HasMany
    {
      return $this->hasMany(User::class, 'college_id', 'id');
    }
}
