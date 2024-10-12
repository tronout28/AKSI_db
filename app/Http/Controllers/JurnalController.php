<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Jurnal;

class JurnalController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $jurnals = Jurnal::where('user_id', $user->id)->get();

        return response()->json([
            'success' => true,
            'message' => 'List jurnal',
            'data' => $jurnals,
        ], 200);
    }

    public function show($id)
    {
        $user = auth()->user();
        $jurnal = Jurnal::where('user_id', $user->id)->where('id', $id)->first();

        if (!$jurnal) {
            return response()->json([
                'success' => false,
                'message' => 'Jurnal not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail jurnal',
            'data' => $jurnal,
        ], 200);
    }

    public function input(Request $request)
    {
        $user = auth()->user();
        
        $request->validate([
            'name_title' => 'required|string',
            'activity' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5000',
        ]);

        $imageName = null;

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '.' . $image->extension(); 
            $image->move(public_path('jurnal'), $imageName); 
        }

        $jurnal = new Jurnal([
            'user_id' => $user->id,
            'name_title' => $request->name_title,
            'activity' => $request->activity,
            'image' => $imageName, 
        ]);
        
        $jurnal->save();

        return response()->json([
            'success' => true,
            'message' => 'Jurnal created',
            'data' => $jurnal,
        ], 201);
    }


    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $jurnal = Jurnal::where('user_id', $user->id)->where('id', $id)->first();

        if (!$jurnal) {
            return response()->json([
                'success' => false,
                'message' => 'Jurnal not found',
            ], 404);
        }

        $request->validate([
            'name_title' => 'nullable|string',
            'activity' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5000',
        ]);

        if ($request->hasFile('image')) {
            if ($jurnal->image && file_exists(public_path('jurnal/' . $jurnal->image))) {
                unlink(public_path('jurnal/' . $jurnal->image));
            }
            $image = $request->file('image');
            $imageName = time() . '.' . $image->extension();
            $image->move(public_path('jurnal'), $imageName);
            
            $jurnal->image = $imageName;
        }

        $jurnal->name_title = $request->name_title ?? $jurnal->name_title;
        $jurnal->activity = $request->activity ?? $jurnal->activity;

        $jurnal->save();

        return response()->json([
            'success' => true,
            'message' => 'Jurnal updated',
            'data' => $jurnal,
        ], 200);
    }


    public function delete($id)
    {
        $user = auth()->user();
        $jurnal = Jurnal::where('user_id', $user->id)->where('id', $id)->first();

        if (!$jurnal) {
            return response()->json([
                'success' => false,
                'message' => 'Jurnal not found',
            ], 404);
        }
        Storage::delete('public/jurnal/'.basename($jurnal->image));
        $jurnal->delete();

        return response()->json([
            'success' => true,
            'message' => 'Jurnal deleted',
        ], 200);
    }

    public function viewJurnalByTimeRange(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user_id = $request->user_id;

        // Retrieve journals for today
        $todayJournals = Jurnal::where('user_id', $user_id)
            ->whereDate('created_at', today())
            ->get();

        // Retrieve journals for the current week
        $weeklyJournals = Jurnal::where('user_id', $user_id)
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->get();

        // Retrieve journals for the current month
        $monthlyJournals = Jurnal::where('user_id', $user_id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->get();

        // Check if any of the journals are empty
        if ($todayJournals->isEmpty() && $weeklyJournals->isEmpty() && $monthlyJournals->isEmpty()) {
            return response()->json([
                'message' => 'No jurnal found for today, this week, or this month',
                'data' => [
                    'today' => [
                        'count' => $todayJournals->count(),
                        'jurnals' => $todayJournals,
                    ],
                    'weekly' => [
                        'count' => $weeklyJournals->count(),
                        'jurnals' => $weeklyJournals,
                    ],
                    'monthly' => [
                        'count' => $monthlyJournals->count(),
                        'jurnals' => $monthlyJournals,
                    ],
                ]
            ], 404);
        }

        // Return the journal data for today, weekly, and monthly
        return response()->json([
            'message' => 'Jurnals data retrieved successfully',
            'data' => [
                'today' => [
                    'count' => $todayJournals->count(),
                    'jurnals' => $todayJournals,
                ],
                'weekly' => [
                    'count' => $weeklyJournals->count(),
                    'jurnals' => $weeklyJournals,
                ],
                'monthly' => [
                    'count' => $monthlyJournals->count(),
                    'jurnals' => $monthlyJournals,
                ],
            ]
        ], 200);
    }

}
