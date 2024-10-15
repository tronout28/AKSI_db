<?php

namespace App\Http\Controllers;

use App\Models\Homeward;
use App\Services\FirebaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;


class HomewardController extends Controller
{
    protected $officeLat = -7.685925;
    protected $officeLong = 110.352091;
    protected $maxDistance = 1;

    protected $firebaseService;
    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today('Asia/Jakarta');

        $existingHomeward = Homeward::where('user_id', $user->id)->whereDate('check_out_time', $today)->first();

        if ($existingHomeward) {
            return response()->json([
                'message' => 'Anda sudah melakukan absensi pulang hari ini.',
            ], 403);
        }

        $userLat = $request->latitude;
        $userLong = $request->longitude;
        $distance = $this->calculateDistance($this->officeLat, $this->officeLong, $userLat, $userLong);

        if ($distance <= $this->maxDistance) {
            $checkOutTime = Carbon::now('Asia/Jakarta');
            $formattedCheckOutTime = $checkOutTime->format('h:i A');

            $homeward = Homeward::create([
                'user_id' => Auth::id(),
                'latitude' => $userLat,
                'longitude' => $userLong,
                'check_out_time' => $checkOutTime,
                'formatted_check_out_time' => $formattedCheckOutTime,
            ]);

            if($homeward) {
                $this->firebaseService->sendNotification($user->notification_token, 'Anda sudah absen pulang', ' Anda telah absen pulang, Hati hati dijalan!' , '');
            }

            return response()->json([
                'message' => 'Absensi berhasil!',
                'formatted_check_out_time' => $formattedCheckOutTime, 
                'homeward' => $homeward,
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'job_title' => $user->job_title,
                ]
            ], 200);
        } else {
            return response()->json(['message' => 'Anda berada di luar area kantor.'], 403);
            $this->firebaseService->sendNotification($user->notification_token, 'Anda berada di luar area kantor', ' Anda tidak bisa absen pulang karena berada di luar area kantor' , '');
        }
    }

    public function getAllHomeward()
    {
        $homeward = Homeward::with('user')->get(); 

        $today = Carbon::today('Asia/Jakarta');
        $todayHomewardCount = Homeward::whereDate('check_out_time', $today)->count();

        return response()->json([
            'homeward' => $homeward,
            'total_today' => $todayHomewardCount, 
        ], 200);
    }

    public function getAttendanceByUserId($userId)
    {
        $homeward = Homeward::with('user')->where('user_id', $userId)->get();

        $monthlyCount = $homeward->groupBy(function($homeward) {
            return Carbon::parse($homeward->check_out_time)->format('Y-m');
        })->map(function($group) {
            return $group->count();
        });

        if ($homeward->isEmpty()) {
            return response()->json(['message' => 'Tidak ada data absensi pulang untuk user ini.'], 404);
        }

        return response()->json([
            'monthly_count' => $monthlyCount,
            'homeward' => $homeward,
        ], 200);
    }
    


    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; 
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
