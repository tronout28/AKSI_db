<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;
use App\Models\Sickness;
use App\Models\Permission;
use App\Services\FirebaseService;
use Carbon\Carbon;

class AttendanceController extends Controller
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

        $existingAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('check_in_time', $today)
            ->first();

        if ($existingAttendance) {
            return response()->json([
                'message' => 'Anda sudah melakukan absensi hari ini.',
            ], 403);
        }

        $sickness = Sickness::where('user_id', $user->id)
            ->whereDate('created_at', $today)
            ->first();

        $permission = Permission::where('user_id', $user->id)
            ->whereDate('created_at', $today)
            ->first();

        if ($sickness || $permission) {
            return response()->json([
                'message' => 'Anda sudah mengajukan izin hari ini, tidak bisa melakukan absensi.',
            ], 403);
        }

        $userLat = $request->latitude;
        $userLong = $request->longitude;
        $distance = $this->calculateDistance($this->officeLat, $this->officeLong, $userLat, $userLong);

        if ($distance <= $this->maxDistance) {
            $checkInTime = Carbon::now('Asia/Jakarta');
            $formattedCheckInTime = $checkInTime->format('h:i A');

            $lateCheckIn = $checkInTime->gt(Carbon::today('Asia/Jakarta')->setTime(8, 0)) ? 'Terlambat' : 'Tepat Waktu';

            $attendance = Attendance::create([
                'user_id' => Auth::id(),
                'latitude' => $userLat,
                'longitude' => $userLong,
                'check_in_time' => $checkInTime,
                'formatted_check_in_time' => $formattedCheckInTime,
                'status' => $lateCheckIn,
            ]);

            if($attendance) {
                $this->firebaseService->sendNotification($user->notification_token, 'Anda sudah absen', 'Anda telah absen dan berada di area kantor', '');
            } elseif($lateCheckIn == 'Terlambat') {
                $this->firebaseService->sendNotification($user->notification_token, 'Anda terlambat absen', 'Anda telah absen dan terlambat masuk kantor', '');
            }

            return response()->json([
                'message' => 'Absensi berhasil!',
                'status' => $lateCheckIn,
                'formatted_check_in_time' => $formattedCheckInTime, 
                'attendance' => $attendance,
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'job_title' => $user->job_title,
                ]
            ], 200);
        } else {
            $this->firebaseService->sendNotification($user->notification_token, 'Anda berada di luar area kantor', 'Anda tidak bisa absen karena berada di luar area kantor', '');
            return response()->json(['message' => 'Anda berada di luar area kantor.'], 403);
        }
    }

    public function getAllAttendances()
    {
        $attendances = Attendance::with('user')->get(); 

        return response()->json([
            'attendances' => $attendances,
        ], 200);
    }

    public function getAttendanceByUserId($userId)
    {
        $attendances = Attendance::with('user')->where('user_id', $userId)->get();

        $monthlyCount = $attendances->groupBy(function($attendance) {
            return Carbon::parse($attendance->check_in_time)->format('Y-m');
        })->map(function($group) {
            return $group->count();
        });

        if ($attendances->isEmpty()) {
            return response()->json(['message' => 'Tidak ada data absensi untuk user ini.'], 404);
        }

        return response()->json([
            'monthly_count' => $monthlyCount,
            'attendances' => $attendances,
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
