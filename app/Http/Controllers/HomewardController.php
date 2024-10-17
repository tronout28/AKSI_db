<?php

namespace App\Http\Controllers;

use App\Models\Homeward;
use App\Services\FirebaseService;
use App\Models\Attendance;
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

    public function scanQrForCheckOut(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today()->format('Y-m-d');
        $scannedQrContent = $request->input('qr_content');
        $expectedQrContent = 'absensi_' . $today;

        // Cek apakah QR code yang discan valid untuk hari ini
        if ($scannedQrContent !== $expectedQrContent) {
            return response()->json([
                'message' => 'Invalid QR code for today!',
            ], 403);
        }

        // Cek apakah user sudah absen masuk hari ini
        $existingAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('check_in_time', $today)
            ->first();

        if (!$existingAttendance) {
            return response()->json([
                'message' => 'Anda belum absen masuk hari ini!',
            ], 403);
        }

        // Cek jarak user dengan kantor
        $userLat = $request->input('latitude');
        $userLong = $request->input('longitude');
        $distance = $this->calculateDistance($this->officeLat, $this->officeLong, $userLat, $userLong);

        if ($distance <= $this->maxDistance) {
            // Simpan absensi pulang
            $checkOutTime = Carbon::now('Asia/Jakarta');
            $formattedCheckOutTime = $checkOutTime->format('h:i A');

            $existingAttendance->update([
                'check_out_time' => $checkOutTime,
                'formatted_check_out_time' => $formattedCheckOutTime,
            ]);

            return response()->json([
                'message' => 'Absensi pulang berhasil!',
                'attendance' => $existingAttendance,
            ], 200);
        } else {
            return response()->json([
                'message' => 'Anda berada di luar area kantor!',
            ], 403);
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

    public function getHomewardByUserId($userId)
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
