<?php
namespace App\Models;

    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;

    class InvitationCard extends Model
    {
        protected $table = 'invitation_cards';

        protected $fillable = [
            'reg_no',
            'secret_key',
            'fullname',
            'position',
            'email',
            'graduate_idnumber',
            'phonenumber',
            'organization',
            'status',
            'approval',
            'type',
            'scanned',
            'date_generated',
            'date_scanned',
            'entrance_user_id', // This is the foreign key
            'pdf',
            // New fields to add via migration:
    'city',
    'stay_overnight',    // maps to your "stay" radio
    'province',
    'district',
    'sector',
    'cell',
    'village',
    'platenumber',
            'is_active'
        ];

        /**
         * Get the user who scanned the card.
         */
        public function scanner(): BelongsTo
        {
            // Second argument is the local foreign key
            // Third argument is the owner key on the Users table
            return $this->belongsTo(User::class, 'entrance_user_id', 'id');
        }
    }
