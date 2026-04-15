<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pickupplace;
use App\Models\Gown;

class PickupplaceController extends Controller
{
public function updatePickup(Request $request)
{
    // Validate that the college exists
    $request->validate([
        'college_id' => 'required|exists:college,id',
    ]);

    // Use updateOrCreate so a student doesn't create multiple pickup rows
    Pickupplace::updateOrCreate(
        ['reg_no' => auth()->user()->reg_no], // Unique identifier for the graduand
        [
            'college_id' => $request->college_id,
            'status' => 'set'
        ]
    );

    return response()->json(['message' => 'Location updated']);
}


public function ViewGown($reg_no) {
      // Retrieve the full record
    $gown = Gown::where('reg_no', $reg_no)->get();

    if (!$gown) {
        return response()->json(['message' => 'Not Found'], 404);
    }

    // Return the whole model object
    return response()->json($gown);
}

public function viewPickup($reg_no)
{
    // Use first() to get a single object instead of a list
    $gown = Pickupplace::with('college')
        ->where('reg_no', $reg_no)
        ->first();

    if (!$gown) {
        return response()->json(['message' => 'Not Found'], 404);
    }

    return response()->json($gown);
}

// Route: Route::get('/view-gown-pickup-per-college/{collegeId}', ...)

public function viewPickupperCollege(Request $request, $collegeId) // Must match {collegeId}
{
    // Initialize query
    $query = Pickupplace::with(['college'])
        ->where('college_id', $collegeId); // Now matches parameter

    // Implement Search Filter
    if ($request->filled('search')) {
        $searchTerm = $request->search;

        $query->where(function($q) use ($searchTerm) {
            $q->where('reg_no', 'LIKE', "%{$searchTerm}%")
              ->orWhereHas('college', function($collegeQuery) use ($searchTerm) {
                  $collegeQuery->where('short_name', 'LIKE', "%{$searchTerm}%");
              });
        });
    }

    // Return Paginated Data
    return response()->json($query->latest()->paginate(10));
}

public function deletePickUp($id)
{
    $record = Pickupplace::find($id);

    if (!$record) {
        return response()->json(['message' => 'Record not found'], 404);
    }

    $record->delete();

    return response()->json(['message' => 'Deleted successfully'], 200);
}
}
