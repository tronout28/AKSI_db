<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tugas;

class TugasController extends Controller
{
    public function storeTugas(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'deadline' => 'required|date_format:Y-m-d H:i:s',
            'title' => 'required|string',
            'description' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:5000',
        ]);

        $imageName = null;

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '.' . $image->extension(); 
            $image->move(public_path('tugas'), $imageName); 
        }

        $tugas = new Tugas([
            'user_id' => $request->user_id,
            'deadline' => $request->deadline,
            'title' => $request->title,
            'description' => $request->description,
            'image' => $imageName,
        ]);
        $tugas->save();

        return response()->json([
            'message' => 'Tugas created successfully!',
            'tugas' => $tugas,
        ], 201);
    }

    public function associateJurnalToTugas(Request $request, $tugasId)
    {
        $request->validate([
            'jurnal_id' => 'required|exists:jurnals,id', // Validate the jurnal_id
        ]);

        $tugas = Tugas::findOrFail($tugasId);
        $tugas->jurnal_id = $request->jurnal_id; // Associate the jurnal
        $tugas->save();

        return response()->json([
            'message' => 'Jurnal associated with Tugas successfully!',
            'tugas' => $tugas,
        ], 200);
    }

    public function updatestatusTugas(Request $request, $tugasId)
    {
        $request->validate([
            'status' => 'required|in:selesai,sedang dikerjakan',
        ]);

        $tugas = Tugas::find($tugasId);
        $tugas->status = $request->status;
        $tugas->save();

        return response()->json([
            'message' => 'Tugas status updated successfully!',
            'tugas' => $tugas,
        ], 200);
    }

    public function getUserTugas($userId)
    {
        // Fetch all tasks for the specific user and include user data
        $tugas = Tugas::with('user')->where('user_id', $userId)->get();

        if ($tugas->isEmpty()) {
            return response()->json([
                'message' => 'No tasks found for this user.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'tugas' => $tugas,
        ], 200);
    }

    public function getAllTugas()
    {
        $tugas = Tugas::with('user')->get();

        if ($tugas->isEmpty()) {
            return response()->json([
                'message' => 'No tasks found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'tugas' => $tugas,
        ], 200);
    }

}
