<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Gown;

class gownController extends Controller
{
    public function issueGown(Request $request) {
    $request->validate([
        'reg_no' => 'required|exists:graduation_list,reg_no',
        'expected_returning_date' => 'required|date',
    ]);

    // Check if they already have a gown issued
    $exists = Gown::where('reg_no', $request->reg_no)->first();
    if ($exists) {
        return response()->json(['message' => 'This student already has an issued gown.'], 422);
    }

    $gown = Gown::create([
        'user_id' => $request->user_id,
        'reg_no' => $request->reg_no,
        'status' => $request->status,
        'expected_returning_date' => $request->expected_returning_date,
        'notes' => $request->notes,
        'returned_date' => null,
    ]);

    return response()->json(['status' => 'success','message' => 'Gown assigned to ' . $request->reg_no]);
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
}
