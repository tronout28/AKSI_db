<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\Attendance;
use App\Models\Sickness;
use Illuminate\Validation\Rule;
use App\Models\Permission;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;


class UserController extends Controller
{
    public function index(Request $request)
    {
        // Get the 'role' parameter from the request
        $role = $request->input('role');

        // Check if 'role' parameter exists and is either 'user' or 'mentor'
        if ($role === 'user' || $role === 'mentor') {
            // Filter users based on the role
            $users = User::where('role', $role)->get();
        } else {
            // If no valid role is provided, return all users
            $users = User::all();
        }

        // Count the number of users retrieved
        $totalUsers = $users->count();

        return response()->json([
            'success' => true,
            'message' => 'List user',
            'total_users' => $totalUsers,
            'data' => $users,
        ], 200);
    }



public function register(Request $request)
{
    $request->validate([
        'name' => 'required|string',
        'email' => 'required|email|unique:users',
        'password' => 'required|string|confirmed',
        'role' => ['required', Rule::in(['mentor', 'user'])],
        'job_tittle' => 'required|string',
        'notification_token' => 'nullable|string',
    ]);

    $user = new User([
        'name' => $request->name,
        'email' => $request->email,
        'password' => bcrypt($request->password),
        'role' => $request->role,  // Save the role
        'job_tittle' => $request->job_tittle,
    ]);
    $user->save();

    return response()->json([
        'message' => 'Successfully created user!',
        'user' => $user->only(['id', 'name', 'email', 'role']),  // Include role in response
    ], 201);
}


public function login(Request $request)
{
    $request->validate([
        'login' => 'required|string',
        'password' => 'required|string',
        'notification_token' => 'nullable|string',
    ], [
        'login.required' => 'Email or username is required.',
        'password.required' => 'Password is required.',
    ]);
    
    $user = User::where('email', $request->login)
                ->orWhere('name', $request->login)
                ->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json([
            'success' => false,
            'message' => 'The provided credentials are incorrect.',
        ], 401);
    }
    
    $token = $user->createToken('auth_token')->plainTextToken;

    // Update the notification token if provided
    if ($request->filled('notification_token')) {
        $user->notification_token = $request->notification_token;
        $user->save();
    }

    return response()->json([
        'success' => true,
        'access_token' => $token,
        'token_type' => 'Bearer',
        'user' => $user->only(['id', 'name', 'email', 'role']),  // Include role in response
    ]);
}


    public function edit(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email,'.$id,
            'job_tittle' => 'required|string',
        ]);

        $user = User::find($id);
        $user->name = $request->name;
        $user->email = $request->email;
        $user->job_tittle = $request->job_tittle;
        $user->save();

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user,
        ]);
    }

    public function details()
    {
        $user = auth()->user();
        $user = User::where('id', $user->id)->first();
        return response()->json([
            'success' => true,
            'message' => 'User details',
            'user' => $user,
        ], 200);
    }

    public function logout()
    {
        $user = User::where('email', auth()->user()->email)->first();
        $user->notification_token = null;
        $user->save();
        $user->tokens()->delete();


        return response()->json([
            'message' => 'Logged out',
        ]);
    }

    public function updateimage(Request $request)
    {
        $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5000',
        ]);

        $user = $request->user();

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time().'.'.$image->extension();
            $image->move(public_path('images-user'), $imageName);
    
            $user->image = $imageName;
        
            if ($user->image && file_exists(public_path('images-user/'.$user->image))) {
                unlink(public_path('images-user/'.$user->image));
            }
        }
        $user->save();

        return response()->json([
            'message' => 'Image updated successfully',
            'image' => $user->image,

        ]);
    }

    public function changepassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|confirmed|min:6', 
        ], [
            'new_password.confirmed' => 'New password confirmation does not match.',
            'new_password.min' => 'New password must be at least 6 characters long.',
        ]);

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'The provided current password is incorrect.',
            ], 401);
        }

        if (Hash::check($request->new_password, $user->password)) {
            return response()->json([
                'message' => 'New password cannot be the same as the current password.',
            ], 400);
        }

        $user->password = Hash::make($request->new_password); 
        $user->save();

        return response()->json([
            'message' => 'Password changed successfully.',
        ]);
    }

    public function delete($id)
    {
        $user = User::find($id);
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }

    public function getMonthlyHistory(Request $request)
    {
        $user = auth()->user();
        $month = $request->input('month', \Carbon\Carbon::now()->month);
        $year = $request->input('year', \Carbon\Carbon::now()->year);

        // Fetch attendance, sickness, and permission records
        $attendances = Attendance::where('user_id', $user->id)
                                ->whereYear('check_in_time', $year)
                                ->whereMonth('check_in_time', $month)
                                ->get();

        $sicknesses = Sickness::where('user_id', $user->id)
                                ->whereYear('created_at', $year)
                                ->whereMonth('created_at', $month)
                                ->whereIn('allowed', ['Diterima', 'Ditolak'])
                                ->get();

        $permissions = Permission::where('user_id', $user->id)
                                ->whereYear('created_at', $year)
                                ->whereMonth('created_at', $month)
                                ->whereIn('allowed', ['Diterima', 'Ditolak'])
                                ->get();

        // Convert each record type to array and add a "type" attribute to differentiate them
        $attendanceData = $attendances->map(function($item) {
            $itemArray = $item->toArray();
            $itemArray['type'] = 'attendance';
            return $itemArray;
        });

        $sicknessData = $sicknesses->map(function($item) {
            $itemArray = $item->toArray();
            $itemArray['type'] = 'sickness';
            return $itemArray;
        });

        $permissionData = $permissions->map(function($item) {
            $itemArray = $item->toArray();
            $itemArray['type'] = 'permission';
            return $itemArray;
        });

        // Convert arrays back to collections before merging
        $mergedData = collect($attendanceData)
                        ->merge($sicknessData)
                        ->merge($permissionData);

        // Sort the merged data by 'created_at' or 'check_in_time'
        $sortedData = $mergedData->sortByDesc(function($item) {
            return $item['created_at'] ?? $item['check_in_time'];
        })->values()->all(); // Reset index to start from 0

        return response()->json([
            'success' => true,
            'message' => 'Monthly attendance, sickness, and permission data',
            'month' => $month,
            'year' => $year,
            'data' => $sortedData,
        ], 200);
    }
}
