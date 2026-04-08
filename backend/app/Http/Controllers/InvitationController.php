<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InvitationCard;
use App\Jobs\ProcessInvitationJob;
use Carbon\Carbon;

class InvitationController extends Controller
{
    public function storeInvitation(Request $request)
    {
        // 1. Validation
        $request->validate([
            'email' => 'required|email',
            'phonenumber' => 'required',
            'fullname' => 'required',
            'reg_no' => 'required',
            'scanned_number' => 'required',
            'parent1_name' => 'required',
            'parent1_id' => 'required',
             'parent1_phone' => 'required',
            'parent2_name' => 'required',
            'parent2_id' => 'required',
             'parent2_phone' => 'required',
        ]);

        $entries = [
            ['name' => $request->fullname, 'phonenumber' => $request->phonenumber,   'id' => $request->scanned_number, 'type' => 'graduand'],
            ['name' => $request->parent1_name, 'phonenumber' => $request->parent1_phone, 'id' => $request->parent1_id,    'type' => 'parent'],
            ['name' => $request->parent2_name, 'phonenumber' => $request->parent2_phone, 'id' => $request->parent2_id,    'type' => 'parent'],
        ];

        $cardIds = [];

foreach ($entries as $entry) {
    // 1. First, try to find if this person already exists
    $existingCard = InvitationCard::where('graduate_idnumber', $entry['id'])
        ->where('type', $entry['type'])
        ->first();

    // 2. Determine the key:
    // If they exist, keep their old key. If they are new, make a fresh one.
    $secretKey = $existingCard ? $existingCard->secret_key : bin2hex(random_bytes(16));

    // 3. Now use updateOrCreate with the specific key for THIS entry
    $card = InvitationCard::updateOrCreate(
        [
            'graduate_idnumber' => $entry['id'],
            'type'              => $entry['type'],
        ],
        [
            'reg_no'     => $request->reg_no,
            'fullname'   => $entry['name'],
            'phonenumber'   => $entry['phonenumber'],
            'email'      => $request->email,
            'status'     => 'pending',
            'is_active'  => true,
            'secret_key' => $secretKey, // This is now guaranteed to be unique or persistent
        ]
    );

    $cardIds[] = $card->id;
}

        // 3. Dispatch to background queue
        ProcessInvitationJob::dispatch($request->email, $cardIds);

        return response()->json([
            'status' => 'success',
            'message' => 'Invitations processed. Profiles updated and processing started.'
        ]);
    }


public function getInvitationData($reg_no)
{
    // Retrieve the full record
    $invitation = InvitationCard::where('reg_no', $reg_no)->get();

    if (!$invitation) {
        return response()->json(['message' => 'Not Found'], 404);
    }

    // Return the whole model object
    return response()->json($invitation);
}

public function viewInvitation(Request $request)
{
    // 1. Start the query and eager load the 'scanner' relationship
    $query = InvitationCard::with(['scanner']);

    // 2. Handle Searching
    if ($request->filled('search')) {
        $search = $request->search;

        $query->where(function($q) use ($search) {
            $q->where('reg_no', 'like', "%{$search}%")
              ->orWhere('graduate_idnumber', 'like', "%{$search}%")
              ->orWhere('phonenumber', 'like', "%{$search}%")
              ->orWhere('fullname', 'like', "%{$search}%") // Local name search
              ->orWhereHas('scanner', function($userQuery) use ($search) {
                  // This searches the name of the staff/user who scanned the card
                  $userQuery->where('fullname', 'like', "%{$search}%");
              });
        });
    }

    // 3. Handle Pagination & Sorting
    // Default to 10 per page if not specified
    $perPage = $request->get('per_page', 10);

    // Order by newest first and paginate
    $invitations = $query->orderBy('id', 'desc')->paginate($perPage);

    // 4. Return as JSON
    return response()->json($invitations);
}


public function updateManualScan(Request $request, $id)
{
    // 1. Find the invitation or fail
    $invitation = InvitationCard::findOrFail($id);

    // 2. Check if it's already scanned to prevent duplicate processing
    if ($invitation->scanned === 'scanned') {
        return response()->json([
            'message' => 'This invitation has already been scanned.'
        ], 422);
    }

    $formattedDate = \Carbon\Carbon::parse($request->date_scanned)->toDateTimeString();
    // 3. Update the record
    $invitation->update([
        'scanned'          => 'Scanned',
        'entrance_user_id' => $request->entrance_user_id, // From Vue payload
        'date_scanned'     => $formattedDate,             // Server-side time
        'status'           => 'Used'                     // Optional: change delivery status
    ]);

    return response()->json([
        'status' => 'success',
        'message' => 'Invitation status updated successfully',
        'data' => $invitation
    ]);
}

public function getScannerStats($user_id)
{


    return response()->json([
        'total_invitations' => InvitationCard::count(),
        'scanned_total'     => InvitationCard::where('scanned', 'scanned')->count(),
        'my_scans'          => InvitationCard::where('scanned', 'scanned')
                                           ->where('entrance_user_id', $user_id)
                                           ->count(),
    ]);
}



public function getScannerStatsByAdmin($user_id)
{
    // 1. Get the general stats
    $totalInvitations = InvitationCard::count();
    $scannedTotal = InvitationCard::where('scanned', 'scanned')->count();

    // 2. Get personal scans for the requested user
    $myScans = InvitationCard::where('scanned', 'scanned')
                ->where('entrance_user_id', $user_id)
                ->count();

    // 3. Get the count for EVERY user who has performed scans
    // We group by entrance_user_id and count the results
    $scansByEachUser = InvitationCard::where('scanned', 'scanned')
        ->with('scanner:id,fullname') // Assumes relationship 'scanner' exists on InvitationCard
        ->selectRaw('entrance_user_id, count(*) as total')
        ->groupBy('entrance_user_id')
        ->get();

    return response()->json([
        'total_invitations'    => $totalInvitations,
        'scanned_total'        => $scannedTotal,
        'my_scans'             => $myScans,
        'scanner_performance'  => $scansByEachUser
    ]);
}

public function viewByQr(Request $request, $secretKey)
    {
        // 1. Find the card by its QR secret key
        $invitation = InvitationCard::where('secret_key', $secretKey)->first();

        if (!$invitation) {
            return response()->json(['message' => 'Invalid QR Code'], 404);
        }

        // 2. Security: Check if already used
        if ($invitation->scanned === 'Scanned') {
            return response()->json([
                'message' => 'Already Scanned!',
                'invitation' => $invitation->load('scanner')
            ], 422);
        }

            $formattedDate = \Carbon\Carbon::parse($request->date_scanned)->toDateTimeString();

        // 3. Update with the ID sent from Vue (entrance_user_id)
        $invitation->update([
            'scanned'          => $request->scanned,
            'entrance_user_id' => $request->entrance_user_id, // From auth.user.id
            'date_scanned'     => $formattedDate,
            'status'           => 'Used'
        ]);

        // 4. Refresh and Load the relationship so Vue can see the name
        $invitation->refresh();
        $invitation->load('scanner');

        return response()->json([
            'message' => 'Check-in Successful!',
            'invitation' => $invitation
        ]);
    }

}
