<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Gown;
use App\Models\InvitationCard;
use App\Jobs\ProcessInvitationJob;


class gownController extends Controller
{
    public function issueGown(Request $request) {
    $request->validate([
        'reg_no' => 'required|exists:graduation_list,reg_no',
        'expected_returning_date' => 'required|date',
        // Validate status to ensure it's "Issued" before sending emails
        'status' => 'required'
    ]);

    // 1. Check if they already have a gown issued
    $exists = Gown::where('reg_no', $request->reg_no)->first();
    if ($exists) {
        return response()->json(['message' => 'This student already has an issued gown.'], 422);
    }

    // 2. Create the Gown record
    $gown = Gown::create([
        'user_id' => $request->user_id,
        'reg_no' => $request->reg_no,
        'status' => $request->status,
        'expected_returning_date' => $request->expected_returning_date,
        'notes' => $request->notes,
        'returned_date' => null,
    ]);

    // 3. Check for Invitation Cards and send emails if status is "Issued"
    if ($gown->status === 'Issued') {
        $invitations = InvitationCard::where('reg_no', $request->reg_no)->get();

        if ($invitations->isNotEmpty()) {
            // Get the email from the first record (they should all be the same)
            $email = $invitations->first()->email;

            // Collect all IDs for this student (Graduand + Parents)
            $cardIds = $invitations->pluck('id')->toArray();

            // 4. Dispatch the Job
            ProcessInvitationJob::dispatch($email, $cardIds);
        }
    }

    return response()->json([
        'message' => 'Gown issued successfully' . ($gown->status === 'Issued' ? ' and invitations well sent to your email.' : '.'),
        'gown' => $gown
    ]);
}

public function showGown(Request $request)
{
    // 1. Eager load user (issuer), receiver, and the student details
    $query = Gown::with(['user', 'receiver', 'student']);

    if ($request->has('search') && $request->filled('search')) {
        $search = $request->search;

        $query->where(function($q) use ($search) {
            $q->where('reg_no', 'like', "%{$search}%")
              ->orWhere('status', 'like', "%{$search}%")
              // 2. This allows searching by student name inside the related table
              ->orWhereHas('student', function($studentQuery) use ($search) {
                  $studentQuery->where('first_name', 'like', "%{$search}%")
                               ->orWhere('last_name', 'like', "%{$search}%");
              });
        });
    }

    $perPage = $request->get('per_page', 10);
    $gowns = $query->orderBy('id', 'desc')->paginate($perPage);

    return response()->json($gowns);
}

      public function deleteGown($id)
    {
        $guest = Gown::find($id);
        if (!$guest) {
            return response()->json(['message' => 'Gown not found'], 404);
        }
        $guest->delete();
        return response()->json(['message' => 'Gown deleted successfully']);
    }


public function returnGown(Request $request, $id)
{
    $gown = Gown::find($id);

    if (!$gown) {
        return response()->json(['message' => 'Gown record not found'], 404);
    }

    // Update the record
    $gown->update([
        'receiver_id'   => $request->receiver_id,
        'status'        => 'Returned',
        'returned_date' => now(), // Laravel helper for current time
        'notes'         => $request->notes ?? $gown->notes
    ]);

    return response()->json([
        'status'  => 'success',
        'message' => 'Gown marked as returned'
    ]);
}


public function getgownStats($user_id)
{
    try {
        return response()->json([
            // Total gowns in the entire system
            'total_invitations' => Gown::count(),

            // Gowns issued by THIS specific user (staff member)
            'totalIssued' => Gown::where('status', 'Issued')
                ->where('user_id', $user_id)
                ->count(),

            // Gowns returned to THIS specific user
            // Note: Changed from InvitationCard to Gown to keep logic consistent
            'totalReturned' => Gown::where('status', 'Returned')
                ->where('user_id', $user_id)
                ->count(),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Could not fetch gown statistics'
        ], 500);
    }
}


}
