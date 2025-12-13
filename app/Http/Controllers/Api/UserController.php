<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->isSuperAdmin() && !auth()->user()->isOperator()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $query = User::query();

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->paginate(15);

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'national_code' => 'required|string|size:10|unique:users',
            'mobile' => 'required|string|regex:/^09\d{9}$/|unique:users',
            'username' => 'required|string|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:super_admin,operator',
        ]);

        $user = User::create([
            'name' => $request->name,
            'last_name' => $request->last_name,
            'national_code' => $request->national_code,
            'mobile' => $request->mobile,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user,
        ], 201);
    }

    public function show($id)
    {
        $user = User::findOrFail($id);

        return response()->json($user);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'national_code' => ['sometimes', 'required', 'string', 'size:10', Rule::unique('users')->ignore($user->id)],
            'mobile' => ['sometimes', 'required', 'string', 'regex:/^09\d{9}$/', Rule::unique('users')->ignore($user->id)],
            'username' => ['sometimes', 'required', 'string', Rule::unique('users')->ignore($user->id)],
            'password' => 'sometimes|string|min:8',
            'role' => 'sometimes|required|in:super_admin,operator,receptor',
        ]);

        $data = $request->only(['name', 'last_name', 'national_code', 'mobile', 'username', 'role']);

        if ($request->has('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user,
        ]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'You cannot delete yourself',
            ], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }
}

