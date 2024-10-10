<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;


class UserController extends Controller
{

    public function details()
    {
        $user = auth()->user();
        $user = User::where('id', $user->id)->first();
        $token = $user->currentAccessToken();
        return response()->json([
            'success' => true,
            'message' => 'User details',
            'user' => $user,
            'token' => $token,

        ], 200);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|confirmed',
            'job_tittle' => 'required|string',
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
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
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
    
        return response()->json([
            'success' => true,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->only(['id', 'name', 'email']), 
        ]);
    }
    

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        

        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }

    public function updateimage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:5000',
        ]);

        $user = $request->user();
        $imageName = time().'.'.$request->image->extension();
        $request->image->move(public_path('images'), $imageName);
        $user->image = $imageName;
        $user->save();

        return response()->json([
            'message' => 'Image updated successfully',
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


}
