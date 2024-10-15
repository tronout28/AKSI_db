<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Permission;
use App\Models\Attendance;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class PermissionController extends Controller
{
    public function index()
    {
        $today = \Carbon\Carbon::today(); 

        $permissions = Permission::with('user')->whereDate('created_at', $today)->orderBy('created_at', 'asc')->get();
        $totalPermissionsToday = $permissions->count();

        return response()->json([
            'success' => true,
            'message' => 'List of permissions today',
            'total_permissions_today' => $totalPermissionsToday,
            'permissions' => $permissions, 
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

        $user = auth()->user();
        $today = Carbon::today('Asia/Jakarta');

        // Cek apakah sudah absen hari ini
        $attendance = Attendance::where('user_id', $user->id)->whereDate('check_in_time', $today)->first();

        if ($attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah absen hari ini, tidak bisa mengajukan izin.',
            ], 403);
        }

        $imageName = null;

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '.' . $image->extension(); 
            $image->move(public_path('permission'), $imageName); 
        }

        $permission = new Permission([
            'user_id' => auth()->user()->id,
            'title' => $request->title,
            'reason' => $request->reason,
            'image' => $imageName,
        ]);
        $permission->save();

        return response()->json([
            'success' => true,
            'message' => 'Successfully input permission!',
            'data' => $permission,
        ], 201);
    }
    
    public function updateAllowedPermission(Request $request, $id)
    {
        $request->validate([
            'allowed' => ['required', Rule::in(['Diterima', 'Ditolak'])],
            'note' => 'nullable|string',
        ]);

        $permission = Permission::find($id);

        if (!$permission) {
            return response()->json(['message' => 'Data sakit tidak ditemukan.'], 404);
        }

        $permission->allowed = $request->allowed;
        $permission->note = $request->note;
        $permission->save(); 

        return response()->json([
            'message' => 'Status permohonan sakit berhasil diperbarui.',
            'permission' => $permission,
        ], 200);
    }
}
