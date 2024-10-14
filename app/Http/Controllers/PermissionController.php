<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        $permission = Permission::with('user')->orderBy('created_at', 'asc')->get();
        return response()->json([
            'success' => true,
            'message' => 'List permission',
            'data' => $permission,
        ], 200);
    }

    public function indexUser()
    {
        $user = auth()->user();
        $permission = Permission::where('user_id', $user->id)->get();
        return response()->json([
            'success' => true,
            'message' => 'List permission',
            'data' => $permission,
        ], 200);
    }

    public function detail($id)
    {
        $permission = Permission::find($id);

        if (!$permission) {
            return response()->json([
                'success' => false,
                'message' => 'Permission not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail permission',
            'data' => $permission,
        ], 200);
    }

    public function input(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'reason' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5000',
        ]);

        $imageName = null;

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '.' . $image->extension(); 
            $image->move(public_path('permission'), $imageName); 
        }

        $permission = new Permission([
            'user_id' => auth()->user()->id,
            'title' => $request->title,
            'reason' => $request->symptoms,
            'image' => $imageName,
        ]);
        $permission->save();

        return response()->json([
            'success' => true,
            'message' => 'Successfully input permission!',
            'data' => $permission,
        ], 201);
    }
    
    public function allowed(Request $request, $id)
    {
        $permission = Permission::find($id);

        $request->validate([
            'note' => 'required|string',
        ]);

        if (!$permission) {
            return response()->json([
                'success' => false,
                'message' => 'Permission not found',
            ], 404);
        }

        $permission->allowed = true;
        $permission->note = $request->note;
        $permission->save();

        return response()->json([
            'success' => true,
            'message' => 'Permission allowed',
            'data' => $permission,
        ], 200);
    }

    public function notallowed(Request $request, $id)
    {
        $permission = Permission::find($id);

        $request->validate([
            'note' => 'required|string',
        ]);

        if (!$permission) {
            return response()->json([
                'success' => false,
                'message' => 'Permission not found',
            ], 404);
        }

        $permission->allowed = false;
        $permission->note = $request->note;
        $permission->save();

        return response()->json([
            'success' => true,
            'message' => 'Permission allowed',
            'data' => $permission,
        ], 200);
    }
}
