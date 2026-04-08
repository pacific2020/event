<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gown extends Model
{
    protected $table = "gown";

    protected $fillable = [
        'user_id',
        'reg_no',
        'expected_returning_date',
        'status',
        'receiver_id',
        'returned_date',
        'notes'
    ];

    /**
     * Get the user who owns the gown.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }


    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id', 'id');
    }

    public function student(): BelongsTo
{
    // This tells Laravel: "Look in the GraduationList model.
    // Match the 'reg_no' in that table with the 'reg_no' in my gown table."
    return $this->belongsTo(GraduationList::class, 'reg_no', 'reg_no');
}
}
