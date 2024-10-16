<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\FirebaseService;
use App\Models\Jurnal;

class JurnalController extends Controller
{
    protected $firebaseService;
    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    public function index()
    {
        $user = auth()->user();
        $today = \Carbon\Carbon::today();

        $jurnals = Jurnal::with('user') ->where('user_id', $user->id)->whereDate('created_at', $today) ->get();
        $totalJurnalsToday = $jurnals->count();

        return response()->json([
            'success' => true,
            'message' => 'List jurnal hari ini',
            'total_jurnal_today' => $totalJurnalsToday, 
            'jurnals' => $jurnals, 
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

        if ($jurnal) {
            $this->firebaseService->sendNotification($user->notification_token, 'Jurnal baru telah dibuat', 'Jurnal anda sudah dikirim ke guru', '');
        }

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

        if ($jurnal) {
            $this->firebaseService->sendNotification($user->notification_token, 'Jurnal telah diupdate', 'Jurnal anda sudah diupdate', '');
        }

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

        if ($jurnal) {
            $this->firebaseService->sendNotification($user->notification_token, 'Jurnal telah dihapus', 'Jurnal anda sudah dihapus', '');
        }

        return response()->json([
            'success' => true,
            'message' => 'Jurnal deleted',
        ], 200);
    }

    public function viewJurnalByTimeRange()
    {
        $todayJournals = Jurnal::with(['user:id,name,email', 'tugas'])
            ->whereDate('created_at', today())
            ->get();

        $weeklyJournals = Jurnal::with(['user:id,name,email', 'tugas'])
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->get();

        $monthlyJournals = Jurnal::with(['user:id,name,email', 'tugas'])
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->get();

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
   
   
    public function getAllJurnals()
    {
        // Get all journals with user and tugas (tasks)
        $jurnals = Jurnal::with(['user:id,name,email', 'tugas'])->get();

        if ($jurnals->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No journals found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'List of all journals retrieved successfully',
            'data' => $jurnals,
        ], 200);
    }


}
