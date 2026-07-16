<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $query = User::withTrashed()->latest();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->has('role') && $request->role !== 'all') {
            $query->where('role', $request->role);
        }

        $users = $query->paginate(10);
        return response()->json(['data' => $users]);
    }

    /**
     * Display the specified user with their orders.
     */
    public function show($id)
    {
        $user = User::withTrashed()->with(['orders' => function ($q) {
            $q->latest();
        }])->findOrFail($id);

        return response()->json(['data' => $user]);
    }

    /**
     * Update the user role.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'role' => 'required|in:admin,member',
        ]);

        $user = User::withTrashed()->findOrFail($id);
        $user->update([
            'role' => $request->role
        ]);

        return response()->json([
            'message' => 'Role updated successfully',
            'data' => $user
        ]);
    }

    /**
     * Soft delete (suspend) a user.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        // Prevent deleting oneself
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Anda tidak bisa menghapus (suspend) akun Anda sendiri'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'User suspended successfully']);
    }

    /**
     * Restore (unsuspend) a user.
     */
    public function restore($id)
    {
        $user = User::withTrashed()->findOrFail($id);
        
        if ($user->trashed()) {
            $user->restore();
            return response()->json(['message' => 'User restored successfully']);
        }

        return response()->json(['message' => 'User tidak dalam status tersuspend'], 400);
    }
}
