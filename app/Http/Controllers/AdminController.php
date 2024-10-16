<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Attendance;
use App\Models\Homeward;
use App\Models\Permission;
use App\Models\Sickness;
use Carbon\Carbon;


class AdminController extends Controller
{

    public function Adminregister(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|confirmed',
            'job_tittle' => 'required|string',
        ]);

        $admin = new Admin([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'job_tittle' => $request->job_tittle,
        ]);
        $admin->save();

        return response()->json([
            'message' => 'Successfully created admin!',
        ], 201);
    }

    public function Adminlogin(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ], [
            'login.required' => 'Email or username is required.',
            'password.required' => 'Password is required.',
        ]);
    
        $admin = Admin::where('email', $request->login)
                    ->orWhere('name', $request->login)
                    ->first();
    
        if (! $admin || ! Hash::check($request->password, $admin->password)) {
            return response()->json([
                'success' => false,
                'message' => 'The provided credentials are incorrect.',
            ], 401);
        }
        $token = $admin->createToken('auth_token')->plainTextToken;
    
        return response()->json([
            'success' => true,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'admin' => $admin->only(['id', 'name', 'email']), 
        ]);
    } 

    public function Admindetails()
    {
        $admin = auth()->user();
        $admin = Admin::where('id', $admin->id)->first();
        return response()->json([
            'success' => true,
            'message' => 'Admin details',
            'admin' => $admin,
        ], 200);
    }


    public function Adminlogout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        

        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }

    public function Adminupdateimage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:5000',
        ]);

        $admin = $request->user();
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time().'.'.$image->extension();
            $image->move(public_path('images-admin'), $imageName);
    
            $admin->image = $imageName;
        
            if ($admin->image && file_exists(public_path('images-admin/'.$admin->image))) {
                unlink(public_path('images-admin/'.$admin->image));
            }
        }
        $admin->save();

        return response()->json([
            'message' => 'Image updated successfully',
            'image' => $admin->image,
        ]);
    }

    public function Adminchangepassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|confirmed|min:6', 
        ], [
            'new_password.confirmed' => 'New password confirmation does not match.',
            'new_password.min' => 'New password must be at least 6 characters long.',
        ]);

        $admin = $request->user();

        if (! Hash::check($request->current_password, $admin->password)) {
            return response()->json([
                'message' => 'The provided current password is incorrect.',
            ], 401);
        }

        if (Hash::check($request->new_password, $admin->password)) {
            return response()->json([
                'message' => 'New password cannot be the same as the current password.',
            ], 400);
        }

        $admin->password = Hash::make($request->new_password); 
        $admin->save();

        return response()->json([
            'message' => 'Password changed successfully.',
        ]);
    }
    
    public function getWeeklySummary()
    {
        $user = auth()->user();
        $startOfWeek = Carbon::now()->startOfWeek(); // Awal minggu (Senin)
        $endOfWeek = Carbon::now()->endOfWeek(); // Akhir minggu (Minggu)

        // Ambil total absensi masuk (check-in) selama minggu ini
        $totalAttendances = Attendance::where('user_id', $user->id)
                                    ->whereBetween('check_in_time', [$startOfWeek, $endOfWeek])
                                    ->count();

        // Ambil total absensi pulang (check-out) selama minggu ini
        $totalHomewards = Homeward::where('user_id', $user->id)
                                ->whereBetween('check_out_time', [$startOfWeek, $endOfWeek])
                                ->count();

        // Ambil total izin yang diterima selama minggu ini
        $totalPermissions = Permission::where('user_id', $user->id)
                                    ->where('status', 'Diterima') // Status enum 'Diterima'
                                    ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
                                    ->count();

        // Ambil total izin sakit yang diterima selama minggu ini
        $totalSicknesses = Sickness::where('user_id', $user->id)
                                ->where('status', 'Diterima') // Status enum 'Diterima'
                                ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
                                ->count();

        // Kembalikan respons dengan hasil total dari semua kategori
        return response()->json([
            'success' => true,
            'message' => 'Weekly summary of attendances, homewards, permissions, and sicknesses',
            'data' => [
                'total_attendances' => $totalAttendances, // Total absen masuk
                'total_homewards' => $totalHomewards,     // Total absen pulang
                'total_permissions' => $totalPermissions, // Total izin yang diterima
                'total_sicknesses' => $totalSicknesses,   // Total izin sakit yang diterima
            ],
        ], 200);
    }

}
