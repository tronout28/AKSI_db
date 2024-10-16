<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sickness;
use App\Models\Attendance;
use App\Services\FirebaseService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class SicknessController extends Controller
{
    protected $firebaseService;
    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    public function index()
    {
        $today = \Carbon\Carbon::today();

        $sickness = Sickness::with('user')->whereDate('created_at', $today)->orderBy('created_at', 'asc')->get();
        $totalSicknessToday = $sickness->count();

        return response()->json([
            'success' => true,
            'message' => 'List of sickness permissions today',
            'total_sickness_today' => $totalSicknessToday,  
            'sickness' => $sickness,    
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

        $user = auth()->user();
        $today = Carbon::today('Asia/Jakarta');
    
        // Cek apakah sudah absen hari ini
        $attendance = Attendance::where('user_id', $user->id)->whereDate('check_in_time', $today)->first();
    
        if ($attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah absen hari ini, tidak bisa mengajukan izin. sakit',
                $this->firebaseService->sendNotification($user->notification_token, 'Anda sudah absen hari ini', ' Anda tidak bisa mengajukan izin sakit' , ''),
            ], 403);
        }

        $existSickness = Sickness::where('user_id', $user->id)->whereDate('created_at', $today)->first();

        if ($existSickness) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah mengajukan izin sakit hari ini',
                $this->firebaseService->sendNotification($user->notification_token, 'Anda sudah mengajukan izin sakit', ' Anda sudah mengajukan izin sakit hari ini' , ''),
            ], 403);
        }

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

        if ($sickness) {
            $this->firebaseService->sendNotification($user->notification_token, 'Anda sudah mengajukan izin sakit', ' Anda telah mengajukan izin sakit, mohon tunggu persetujuan dari admin' , '');
        }

        return response()->json([
            'success' => true,
            'message' => 'Successfully input sickness!',
            'data' => $sickness,
        ], 201);
    }
    
    public function updateAllowedSickness(Request $request, $id)
    {
        $request->validate([
            'allowed' => ['required', Rule::in(['Diterima', 'Ditolak'])],
            'note' => 'nullable|string',
        ]);

        $sickness = Sickness::find($id);

        if (!$sickness) {
            return response()->json(['message' => 'Data sakit tidak ditemukan.'], 404);
        }

        $sickness->allowed = $request->allowed;
        $sickness->note = $request->note;
        $sickness->save(); 

        $user = User::find($sickness->user_id);

        if ($sickness->allowed == 'Diterima') {
            $this->firebaseService->sendNotification($user->notification_token, 'Izin sakit diterima', 'Izin sakit anda telah disetujui', '');
        } elseif ($sickness->allowed == 'Ditolak') {
            $this->firebaseService->sendNotification($user->notification_token, 'Izin sakit ditolak', 'Izin sakit anda ditolak', '');
        }

        return response()->json([
            'message' => 'Status permohonan sakit berhasil diperbarui.',
            'sickness' => $sickness,
        ], 200);
    }

}
