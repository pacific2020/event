<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InvitationCard;
use App\Jobs\ProcessInvitationJob;
use Carbon\Carbon;
use App\Models\Gown;

class InvitationController extends Controller
{

public function storeInvitation(Request $request)
{
    // 1. Validation (Same as before)
    $rules = [
        'email' => 'required|email',
        'phonenumber' => 'required',
        'fullname' => 'required',
        'reg_no' => 'required',
        'scanned_number' => 'required',
        'city' => 'required',
        'stay' => 'required',
    ];

    if ($request->filled('parent1_name')) {
        $rules['parent1_id'] = 'required';
        $rules['parent1_phone'] = 'required';
        $rules['parent1_province'] = 'required';
        $rules['parent1_district'] = 'required';
        $rules['parent1_sector'] = 'required';
        $rules['parent1_cell'] = 'required';
        $rules['parent1_village'] = 'required';
        $rules['parent1_stay'] = 'required';
    }

    if ($request->filled('parent2_name')) {
        $rules['parent2_id'] = 'required';
        $rules['parent2_phone'] = 'required';
        $rules['parent2_province'] = 'required';
        $rules['parent2_district'] = 'required';
        $rules['parent2_sector'] = 'required';
        $rules['parent2_cell'] = 'required';
        $rules['parent2_village'] = 'required';
        $rules['parent2_stay'] = 'required';
    }

    $request->validate($rules);

    // 2. Prepare Data Entries
    $entries = [];
    $entries[] = [
        'type' => 'graduand',
        'id' => $request->scanned_number,
        'name' => $request->fullname,
        'phone' => $request->phonenumber,
        'city' => $request->city,
        'province' => null,
        'stay' => $request->stay,
    ];

    if ($request->filled('parent1_name')) {
        $entries[] = [
            'type' => 'parent',
            'id' => $request->parent1_id,
            'name' => $request->parent1_name,
            'phone' => $request->parent1_phone,
            'city' => null,
            'province' => $request->parent1_province,
            'district' => $request->parent1_district,
            'sector' => $request->parent1_sector,
            'cell' => $request->parent1_cell,
            'village' => $request->parent1_village,
            'stay' => $request->parent1_stay,
        ];
    }

    if ($request->filled('parent2_name')) {
        $entries[] = [
            'type' => 'parent',
            'id' => $request->parent2_id,
            'name' => $request->parent2_name,
            'phone' => $request->parent2_phone,
            'city' => null,
            'province' => $request->parent2_province,
            'district' => $request->parent2_district,
            'sector' => $request->parent2_sector,
            'cell' => $request->parent2_cell,
            'village' => $request->parent2_village,
            'stay' => $request->parent2_stay,
        ];
    }

    $cardIds = [];

    // 3. Database Transaction (Data is saved HERE)
    \DB::transaction(function () use ($entries, $request, &$cardIds) {
        foreach ($entries as $entry) {
            $existing = InvitationCard::where('graduate_idnumber', $entry['id'])
                ->where('type', $entry['type'])
                ->first();

            $card = InvitationCard::updateOrCreate(
                [
                    'graduate_idnumber' => $entry['id'],
                    'type' => $entry['type']
                ],
                [
                    'reg_no' => $request->reg_no,
                    'fullname' => $entry['name'],
                    'phonenumber' => $entry['phone'],
                    'email' => $request->email,
                    'city' => $entry['city'] ?? null,
                    'province' => $entry['province'] ?? null,
                    'district' => $entry['district'] ?? null,
                     'sector' => $entry['sector'] ?? null,
                     'cell' => $entry['cell'] ?? null,
                     'village' => $entry['village'] ?? null,
                    'stay_overnight' => $entry['stay'],
                    'secret_key' => $existing ? $existing->secret_key : bin2hex(random_bytes(16)),
                    'status' => 'pending',
                    'is_active' => true,
                ]
            );
            $cardIds[] = $card->id;
        }
    });

    // 4. Gown Status Check (Move AFTER the transaction)
    $gown = Gown::where('reg_no', $request->reg_no)->first();

    if (!$gown || $gown->status !== 'Issued') {
        return response()->json([
            'message' => 'Details updated successfully! However, invitations will only be sent after your gown status is marked as "Issued". Current status: ' . ($gown->status ?? 'Not Found'),
            'status' => 'gown_pending',
            'ids' => $cardIds
        ], 200); // Return 200 because the data update was actually successful
    }

    // 5. Dispatch the Job (Only if gown check passed)
    ProcessInvitationJob::dispatch($request->email, $cardIds);

    return response()->json([
        'message' => 'Details updated! Invitations are being generated and sent to your email.',
        'ids' => $cardIds
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



    public function confirmAttendance(Request $request)
    {
        // 1. Validate the incoming request
        $validated = $request->validate([
            'email'           => 'required|email',
            'fullname'        => 'required|string|max:255',
            'phonenumber'     => 'required|string',
            'organization'    => 'required|string',
            'attendance_type' => 'required|in:self,delegate,not',
            'plate_number'    => 'nullable|string|max:20',
        ]);

        try {
            // 2. Find the invitation by email
            $invitation = InvitationCard::where('email', $validated['email'])->first();

            if (!$invitation) {
                return response()->json(['message' => 'Invitation record not found.'], 404);
            }

            // 3. Update the record


            $invitation->update([
                'fullname'     => $validated['fullname'], // Updates name if delegate is attending
                'phonenumber'  => $validated['phonenumber'],
                'platenumber'     => $validated['organization'], // Assuming position stores org info
                'approval'       => $validated['attendance_type'],
                'platenumber' => $validated['plate_number'] ?? null,
            ]);

            return response()->json([
                'message' => 'Thank you! Your response has been recorded successfully.'
            ], 200);

        } catch (\Exception $e) {
            Log::error("Confirmation Error: " . $e->getMessage());
            return response()->json(['message' => 'A server error occurred.'], 500);
        }
    }

}
