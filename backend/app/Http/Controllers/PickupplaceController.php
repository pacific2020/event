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

}
