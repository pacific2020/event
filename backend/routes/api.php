<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CollegesController;
use App\Http\Controllers\CreateEventController;

use App\Http\Controllers\excelController;
use App\Http\Controllers\GraduationListController;
use App\Http\Controllers\gownController;

use App\Http\Controllers\PickupplaceController;

use App\Http\Controllers\InvitationController;




// Login
// Login
Route::post('/login', [UserController::class, 'login']);

// Get logged in user
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return response()->json($request->user());
});

Route::middleware('auth:sanctum')->get('/colleges', [ CollegesController::class, 'index'] );

Route::middleware('auth:sanctum')->post('/createUsers', [ UserController::class, 'create'] );

Route::get('/viewUsers', [ UserController::class, 'show'] );

Route::delete('/delete/user/{id}', [ UserController::class, 'destroy'] );

// Logout
Route::middleware('auth:sanctum')->post('/logout', function (Request $request) {

    $request->user()->currentAccessToken()->delete();

    return response()->json([
        'status' => 'success',
        'message' => 'Logged out successfully'
    ]);
});







Route::middleware('auth:sanctum')->group(function () {
Route::apiResource('/createEvent', CreateEventController::class)
    ->except(['create', 'edit', 'dalete']);
});

Route::get('/eventsPublic', [CreateEventController::class, 'showPublic']);

Route::get('/eventsCount', [CreateEventController::class, 'getStats']);
Route::post('/guest/confirm-attendance', [InvitationController::class, 'confirmAttendance']);


Route::middleware('auth:sanctum')->get('/viewEvent', [CreateEventController::class, 'viewEvent']);
Route::middleware('auth:sanctum')->delete('/deleteEvent/{id}', [CreateEventController::class, 'deleteEvent']);



Route::middleware('auth:sanctum')->get('/viewGuestInvitation', [excelController::class, 'viewGuestInvitation']);

Route::middleware('auth:sanctum')->delete('/deleteGuest/{id}', [excelController::class, 'deleteGuest']);
Route::middleware('auth:sanctum')->post('/send-invitations', [excelController::class, 'sendInvitations']);

Route::middleware('auth:sanctum')->post('/send-reminders', [excelController::class, 'sendReminders']);

Route::middleware('auth:sanctum')->get('/graduation-list', [GraduationListController::class, 'show']);

Route::middleware('auth:sanctum')->delete('/deleteGraduant/{id}', [GraduationListController::class, 'deleteGraduant']);


Route::post('/graduand/login', [GraduationListController::class, 'login']);

// Get logged in user
Route::middleware('auth:sanctum')->get('/graduandUser', function (Request $request) {
   return response()->json($request->user());
});


// Logout
Route::middleware('auth:sanctum')->post('/GraduandLogout', function (Request $request) {

    $request->user()->currentAccessToken()->delete();

    return response()->json([
        'status' => 'success',
        'message' => 'Logged out successfully'
    ]);
});


Route::middleware('auth:sanctum')->get('/get-graduand', [GraduationListController::class, 'showGraduand']);



Route::middleware('auth:sanctum')->get('/gown-stats/{user_id}', [gownController::class, 'getgownStats']);
Route::middleware('auth:sanctum')->post('/issue-gown', [gownController::class, 'issueGown']);


Route::middleware('auth:sanctum')->get('/gown-list', [gownController::class, 'showGown']);


Route::middleware('auth:sanctum')->delete('/deleteGown/{id}', [gownController::class, 'deleteGown']);

Route::middleware('auth:sanctum')->post('/returnGown/{id}', [gownController::class, 'returnGown']);


Route::middleware('auth:sanctum')->post('/set-gown-pickup', [PickupplaceController::class, 'updatePickup']);

Route::middleware('auth:sanctum')->get('/view-gown-pickup/{reg_no}', [PickupplaceController::class, 'viewPickup']);

Route::middleware('auth:sanctum')->get('/view-gown-pickup-per-college/{collegeId}', [PickupplaceController::class, 'viewPickupperCollege']);
Route::middleware('auth:sanctum')->delete('/deletePickUp/{id}', [PickupplaceController::class, 'deletePickUp']);

Route::middleware('auth:sanctum')->get('/view-gown/{reg_no}', [PickupplaceController::class, 'ViewGown']);

Route::middleware('auth:sanctum')->post('/submit-invitation-details', [InvitationController::class, 'storeInvitation']);


Route::middleware('auth:sanctum')->get('/get-invitation-data/{reg_no}', [InvitationController::class, 'getInvitationData']);

Route::middleware('auth:sanctum')->get('/view-invitations', [InvitationController::class, 'viewInvitation']);

Route::middleware('auth:sanctum')->post('/update-invitation-scan/{id}', [InvitationController::class, 'updateManualScan']);


Route::middleware('auth:sanctum')->get('/scanner-stats/{user_id}', [InvitationController::class, 'getScannerStats']);

Route::middleware('auth:sanctum')->get('/scanner-stats-admin/{user_id}', [InvitationController::class, 'getScannerStatsByAdmin']);

Route::middleware('auth:sanctum')->post('/auto-scan/{secretKey}', [InvitationController::class, 'viewByQr']);







?>
