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

        $existingAttendance = Attendance::where('user_id', $user->id)->whereDate('check_in_time', $today)->first();

        if ($existingAttendance) {
            return response()->json([
                'message' => 'Anda sudah melakukan absensi hari ini.',
                $this->firebaseService->sendNotification($user->notification_token, 'Anda sudah absen hari ini', ' Anda tidak bisa absen lagi karena sudah absen hari ini' , ''),
            ], 403);
        }

        $sickness = Sickness::where('user_id', $user->id)->whereDate('created_at', $today)->first();

        $permission = Permission::where('user_id', $user->id)->whereDate('created_at', $today)->first();

        if ($sickness || $permission) {
            return response()->json([
                'message' => 'Anda sudah mengajukan izin hari ini, tidak bisa melakukan absensi.',
                $this->firebaseService->sendNotification($user->notification_token, 'Anda sudah mengajukan izin', ' Anda tidak bisa absen karena sudah mengajukan izin' , ''),
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
                $this->firebaseService->sendNotification($user->notification_token, 'Anda sudah absen', ' Anda telah absen dan telah berada di area kantor' , '');
            }elseif($lateCheckIn == 'Terlambat'){
                $this->firebaseService->sendNotification($user->notification_token, 'Anda terlambat absen', ' Anda telah absen dan terlambat masuk kantor' , '');
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
            return response()->json(['message' => 'Anda berada di luar area kantor.'], 403);
            $this->firebaseService->sendNotification($user->notification_token, 'Anda berada di luar area kantor', ' Anda tidak bisa absen karena berada di luar area kantor' , '');
        }
    }

    public function getAllAttendances()
    {
        $attendances = Attendance::with('user')->get(); 

        $today = Carbon::today('Asia/Jakarta');
        $todayAttendanceCount = Attendance::whereDate('check_in_time', $today)->count();

        return response()->json([
            'attendances' => $attendances,
            'total_today' => $todayAttendanceCount, 
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

    public function filterAttendances(Request $request)
    {
        $filter = $request->input('filter', 'all');
        
        $today = Carbon::today('Asia/Jakarta');
        $query = Attendance::with('user');

        // Terapkan filter berdasarkan filter yang dipilih
        switch ($filter) {
            case 'daily': // Absensi hari ini
                $query->whereDate('check_in_time', $today);
                break;

            case 'weekly': // Absensi untuk 7 hari terakhir
                $startOfWeek = Carbon::now('Asia/Jakarta')->startOfWeek();
                $endOfWeek = Carbon::now('Asia/Jakarta')->endOfWeek();
                $query->whereBetween('check_in_time', [$startOfWeek, $endOfWeek]);
                break;

            case 'monthly': // Absensi untuk bulan ini
                $query->whereMonth('check_in_time', $today->month)
                    ->whereYear('check_in_time', $today->year);
                break;

            case 'all': // Tanpa filter, ambil semua absensi
            default:
                // Tidak ada filter tambahan yang diterapkan
                break;
        }

        // Ambil data absensi yang difilter
        $attendances = $query->get();

        // Format respons dengan informasi tambahan tentang pengguna
        $formattedAttendances = $attendances->map(function ($attendance) {
            return [
                'user' => [
                    'name' => $attendance->user->name,
                    'email' => $attendance->user->email,
                    'job_tittle' => $attendance->user->job_tittle, // Berikan fallback jika job_title null
                ],
                'check_in_time' => Carbon::parse($attendance->check_in_time)->format('Y-m-d H:i:s'), // Memastikan ini instance Carbon
                'latitude' => $attendance->latitude,
                'longitude' => $attendance->longitude,
                'status' => $attendance->status,
            ];
        });

        $totalAttendances = $attendances->count();

        return response()->json([
            'success' => true,
            'message' => 'Filtered attendances',
            'filter' => $filter,
            'total' => $totalAttendances,
            'attendances' => $formattedAttendances, // Menampilkan hasil yang diformat
        ], 200);
    }


   public function getWeeklyAttendanceChart()
    {
        $today = Carbon::today('Asia/Jakarta');
        $startDate = $today->copy()->subDays(6); // Mulai dari 6 hari sebelum hari ini

        // Query untuk mendapatkan data absensi dalam rentang waktu 7 hari terakhir
        $attendances = Attendance::whereBetween('check_in_time', [$startDate->startOfDay(), $today->endOfDay()])
            ->selectRaw('DATE(check_in_time) as date, COUNT(*) as total')
            ->groupBy('date')
            ->get();

        // Membuat daftar tanggal dari 7 hari terakhir
        $dates = [];
        for ($i = 0; $i <= 6; $i++) {
            $dates[] = $startDate->copy()->addDays($i); // Simpan instance Carbon
        }

        // Mengisi chartData berdasarkan hari, dengan total kehadiran jika ada, atau 0 jika tidak ada
        $chartData = [];
        foreach ($dates as $date) {
            $attendanceForDate = $attendances->firstWhere('date', $date->format('Y-m-d')); // Cocokkan berdasarkan tanggal
            $chartData[] = [
                'day' => $date->locale('id')->isoFormat('dddd'), // Format nama hari dalam bahasa Indonesia
                'total' => $attendanceForDate ? (int) $attendanceForDate->total : 0, // Paksa total menjadi integer
            ];
        }

        return response()->json([
            'message' => 'Data absensi untuk 7 hari terakhir',
            'chart' => $chartData,
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
