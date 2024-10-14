<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sickness;

class SicknessController extends Controller
{
    public function index()
    {
        $sickness = Sickness::with('user')->orderBy('created_at', 'asc')->get();
        return response()->json([
            'success' => true,
            'message' => 'List sickness',
            'data' => $sickness,
        ], 200);
    }

    public function indexUser()
    {
        $user = auth()->user();
        $sickness = Sickness::where('user_id', $user->id)->get();
        return response()->json([
            'success' => true,
            'message' => 'List sickness',
            'data' => $sickness,
        ], 200);
    }

    public function detail($id)
    {
        $sickness = Sickness::find($id);

        if (!$sickness) {
            return response()->json([
                'success' => false,
                'message' => 'Sickness not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail sickness',
            'data' => $sickness,
        ], 200);
    }

    public function input(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'symptoms' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5000',
        ]);

        $imageName = null;

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '.' . $image->extension(); 
            $image->move(public_path('sickness'), $imageName); 
        }

        $sickness = new Sickness([
            'user_id' => auth()->user()->id,
            'title' => $request->title,
            'symptoms' => $request->symptoms,
            'image' => $imageName,
        ]);
        $sickness->save();

        return response()->json([
            'success' => true,
            'message' => 'Successfully input sickness!',
            'data' => $sickness,
        ], 201);
    }
    
    public function allowed(Request $request, $id)
    {
        $sickness = Sickness::find($id);

        $request->validate([
            'note' => 'required|string',
        ]);

        if (!$sickness) {
            return response()->json([
                'success' => false,
                'message' => 'Sickness not found',
            ], 404);
        }

        $sickness->allowed = true;
        $sickness->note = $request->note;
        $sickness->save();

        return response()->json([
            'success' => true,
            'message' => 'Sickness allowed',
            'data' => $sickness,
        ], 200);
    }

    public function notallowed(Request $request, $id)
    {
        $sickness = Sickness::find($id);

        $request->validate([
            'note' => 'required|string',
        ]);

        if (!$sickness) {
            return response()->json([
                'success' => false,
                'message' => 'Sickness not found',
            ], 404);
        }

        $sickness->allowed = false;
        $sickness->note = $request->note;
        $sickness->save();

        return response()->json([
            'success' => true,
            'message' => 'Sickness allowed',
            'data' => $sickness,
        ], 200);
    }
}
