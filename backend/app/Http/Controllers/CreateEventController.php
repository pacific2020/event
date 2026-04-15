<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\InvitationCard;
use App\Models\GraduationList;

class CreateEventController extends Controller
{
    public function store(Request $request)
    {
        // ✅ Validation
        $validator = Validator::make($request->all(), [
            'category' => 'required|string|max:255',
            'event_name' => 'required|string|max:255',
            'starting_date' => 'required|date',
            'ending_date' => 'required|date|after_or_equal:starting_date',
            'expected_invitation' => 'required|integer|min:1',
            'generated_at' => 'required|date',
            'image' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // ✅ Handle image upload
            $imagePath = null;

            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('events', 'public');
            }

            // ✅ Create event
            $event = Event::create([
                'category' => $request->category,
                'event_name' => $request->event_name,
                'starting_date' => $request->starting_date,
                'ending_date' => $request->ending_date,
                'expected_invitation' => $request->expected_invitation,
                'generated_at' => $request->generated_at,
                'image' => $imagePath,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Event created successfully',
                'data' => $event
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create event',
                'error' => $e->getMessage()
            ], 500);
        }
    }



public function showPublic(Request $request)
{
    $events = Event::orderBy('id', 'desc')
        ->where('is_active', true)
        ->get();

    return response()->json([
        'message' => 'Public events fetched successfully',
        'data' => $events
    ]);
}

public function viewEvent(Request $request){
   $query = Event::query();

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('category', 'like', "%{$search}%")
                  ->orWhere('event_name', 'like', "%{$search}%")
                  ->orWhere('starting_date', 'like', "%{$search}%")
                  ->orWhere('ending_date', 'like', "%{$search}%")
                   ->orWhere('expected_invitation', 'like', "%{$search}%")
                   ->orWhere('generated_at', 'like', "%{$search}%");
            });
        }

        $perPage = 10;
        $guests = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json($guests);
}

public function deleteEvent($id){
       $guest = Event::find($id);
        if (!$guest) {
            return response()->json(['message' => 'Guest not found'], 404);
        }
        $guest->delete();
        return response()->json(['message' => 'Guest deleted successfully']);
}


public function getStats() {
    return response()->json([
        'events' => Event::count(),
        'invitations' => InvitationCard::count(),
        'graduands' => GraduationList::count()
    ]);
}


}
