<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GraduationList;

class GraduationListController extends Controller
{
public function show(Request $request)
    {
        $query = GraduationList::query();

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('college_name', 'like', "%{$search}%")
                  ->orWhere('degree', 'like', "%{$search}%")
                   ->orWhere('scanned_number', 'like', "%{$search}%");
            });
        }

        $perPage = 10;
        $guests = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json($guests);
    }

      public function deleteGraduant($id)
    {
        $guest = GraduationList::find($id);
        if (!$guest) {
            return response()->json(['message' => 'Graduant not found'], 404);
        }
        $guest->delete();
        return response()->json(['message' => 'Graduant deleted successfully']);
    }

public function login(Request $request)
{
    $request->validate([
        'reg_no' => 'required|string',
        'scanned_number' => 'required|string',
    ]);

    $graduand = GraduationList::where('reg_no', $request->reg_no)->first();

    if ($graduand && $graduand->scanned_number === $request->scanned_number) {

        // --- ADDED: Generate Sanctum Token ---
        // 'graduand-token' is just a name for the token
        $token = $graduand->createToken('token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'token' => $token, // Send this to Vue
            'graduand' => $graduand
        ], 200);
    }

    return response()->json([
        'status' => 'error',
        'message' => 'Invalid Registration Number or ID Number'
    ], 401);
}

public function showGraduand() {
    // 1. You MUST call ->get() to actually fetch the collection from the database
    $graduand = GraduationList::orderBy('id', 'desc')->get();

    return response()->json([
        'status' => 'success',
        'message' => 'Data retrieved successfully',
        'graduand' => $graduand // This will now be an array of student objects
    ], 200);
}




}
