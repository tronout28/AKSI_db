<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use Auth;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    protected $officeLat = -7.685925;
    protected $officeLong = 110.352091;
    protected $maxDistance = 0.1;

    public function store(Request $request)
{
    $userLat = $request->latitude;
    $userLong = $request->longitude;

    // Menghitung jarak antara user dan kantor
    $distance = $this->calculateDistance($this->officeLat, $this->officeLong, $userLat, $userLong);

    if ($distance <= $this->maxDistance) {
        // Menggunakan timezone Asia/Jakarta untuk waktu check-in
        $checkInTime = Carbon::now('Asia/Jakarta');
        $formattedCheckInTime = $checkInTime->format('h:i A');

        // Menentukan apakah check-in terlambat atau tepat waktu
        if ($checkInTime->between(Carbon::today('Asia/Jakarta')->setTime(1, 0), Carbon::today('Asia/Jakarta')->setTime(8, 0))) {
            $lateCheckIn = 'Tepat Waktu';
        } else if ($checkInTime->gt(Carbon::today('Asia/Jakarta')->setTime(8, 0))) {
            $lateCheckIn = 'Terlambat';
        } else {
            $lateCheckIn = 'Tepat Waktu'; // Sebagai pengaman, jika sebelum jam 1 dianggap tepat waktu
        }

        // Menyimpan data absensi ke database
        $attendance = Attendance::create([
            'user_id' => Auth::id(),
            'latitude' => $userLat,
            'longitude' => $userLong,
            'check_in_time' => $checkInTime, // Tetap simpan dalam Asia/Jakarta
            'status' => $lateCheckIn,
        ]);

        // Mengambil data user saat ini
        $user = Auth::user();

        return response()->json([
            'message' => 'Absensi berhasil!',
            'status' => $lateCheckIn,
            'formatted_check_in_time' => $formattedCheckInTime, // Menampilkan check-in time yang sesuai
            'attendance' => $attendance,
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'job_title' => $user->job_title,
            ]
        ], 200);
    } else {
        return response()->json(['message' => 'Anda berada di luar area kantor.'], 403);
    }
}


public function getAllAttendances()
{
    $attendances = Attendance::with('user')->get()->map(function($attendance) {
        // Mengambil waktu yang sesuai dengan timezone Asia/Jakarta
        $attendance->formatted_check_in_time = Carbon::parse($attendance->check_in_time)->timezone('Asia/Jakarta')->format('h:i A');
        return $attendance;
    });

    return response()->json([
        'attendances' => $attendances,
    ], 200);
}

public function getAttendanceByUserId($userId)
{
    $attendances = Attendance::with('user')->where('user_id', $userId)->get();

    // Menghitung jumlah absensi per bulan
    $monthlyCount = $attendances->groupBy(function($attendance) {
        return Carbon::parse($attendance->check_in_time)->timezone('Asia/Jakarta')->format('Y-m');
    })->map(function($group) {
        return $group->count();
    });

    if ($attendances->isEmpty()) {
        return response()->json(['message' => 'Tidak ada data absensi untuk user ini.'], 404);
    }

    // Menambahkan formatted_check_in_time
    $attendances = $attendances->map(function($attendance) {
        $attendance->formatted_check_in_time = Carbon::parse($attendance->check_in_time)->timezone('Asia/Jakarta')->format('h:i A');
        return $attendance;
    });

    return response()->json([
        'monthly_count' => $monthlyCount,
        'attendances' => $attendances,
    ], 200);
}



    // Fungsi untuk menghitung jarak antara dua titik (koordinat) menggunakan rumus Haversine
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Radius bumi dalam kilometer
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
