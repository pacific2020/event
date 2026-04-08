<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail; // <--- ADD THIS
use App\Mail\UserCreatedMail;

class UserController extends Controller
{
    public function login(Request $request){
         $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

         // 2️⃣ Check if user exists
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email not found'
            ], 404);
        }

        // 3️⃣ Check password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Incorrect password'
            ], 401);
        }

        // 4️⃣ Create API token (no session needed)
        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'role' =>$user->position,
            'message' => 'Login successful',
            'access_token' => $token,
            'token_type' => 'Bearer'
        ]);



    }


public function create(Request $request)
{
    $request->validate([
        'college_id' => 'required|exists:college,id',
        'position'   => 'required|string',
        'fullname'   => 'required|string|max:255',
        'email'      => 'required|email|unique:users,email',
        'password'   => 'required|min:6',
        'phone'      => 'required|string',
        'is_active'  => 'sometimes|boolean'
    ]);

    // Save user
    $user = User::create([
        'college_id'   => $request->college_id,
        'position'     => $request->position,
        'fullname'     => $request->fullname,
        'email'        => $request->email,
        'password'     => Hash::make($request->password),
        'phone_number' => $request->phone,
        'is_active'    => $request->is_active ?? 1,
    ]);

    // ✅ Send the email
    // We pass the raw $request->password because the hashed one is unreadable
    try {
        Mail::to($user->email)->send(new UserCreatedMail($user, $request->password));
    } catch (\Exception $e) {
        // Log error but don't stop the process if mail fails
        \Log::error("Mail failed: " . $e->getMessage());
    }

    return response()->json([
        'status' => 'success',
        'message' => 'User created and notification email sent',
        'data' => $user
    ]);
}


public function show(Request $request)
{
    $query = User::with('college');

    if ($request->filled('search')) {
        $query->where('fullname', 'like', '%' . $request->search . '%')
              ->orWhere('email', 'like', '%' . $request->search . '%');
    }

    return response()->json([
        'status' => 'success',
        'data' => $query->paginate(10)
    ]);
}


public function destroy($id)
{
    $user = User::findOrFail($id); // find user or fail
    $user->delete(); // delete it

    return response()->json([
        'status' => 'success',
        'message' => 'User deleted successfully'
    ]);
}


}
