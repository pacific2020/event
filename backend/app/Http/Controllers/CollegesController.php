<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\College;

class CollegesController extends Controller
{
    public function index(Request $request)
    {
        $college = College::orderBy('id', 'asc')->get();

        return response()->json([
            'status' =>'success',
            'data' => $college
        ]);
    }
}
