<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;


class UserController extends Controller
{
    public function index()
{
    $users = User::all();
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
            'job_tittle' => 'required|string',
            'notification_token' => 'nullable|string',
        ]);

        $user = new User([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'job_tittle' => $request->job_tittle,
        ]);
        $user->save();

        return response()->json([
            'message' => 'Successfully created user!',
            'user' => $user,
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
    
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'The provided credentials are incorrect.',
            ], 401);
        }
        $token = $user->createToken('auth_token')->plainTextToken;
        
        if ($request->filled('notification_token')) {
            $user->notification_token = $request->notification_token;
            $user->save();
        }
    
        return response()->json([
            'success' => true,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->only(['id', 'name', 'email']), 
        ]);
    }

    public function edit(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string',
            'email' => 'nullable|email',
            'job_tittle' => 'nullable|string',
        ]);

        $user = User::find($request->user()->id);
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

    public function delete(Request $request)
    {
        $user = User::find($request->user()->id);
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }
}
