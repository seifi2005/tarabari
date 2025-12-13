<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Receptor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ReceptorController extends Controller
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
        $receptors = Receptor::with('user')->paginate(15);

        return response()->json($receptors);
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'mobile' => 'required|string|regex:/^09\d{9}$/|unique:receptors',
            'allowed_ip' => 'nullable|ip',
            'username' => 'required|string|unique:receptors',
            'password' => 'required|string|min:8',
        ]);

        $receptor = Receptor::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'company_name' => $request->company_name,
            'mobile' => $request->mobile,
            'allowed_ip' => $request->allowed_ip,
            'username' => $request->username,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Receptor created successfully',
            'receptor' => $receptor->load('user'),
        ], 201);
    }

    public function show($id)
    {
        $receptor = Receptor::with('user')->findOrFail($id);

        return response()->json($receptor);
    }

    public function update(Request $request, $id)
    {
        $receptor = Receptor::findOrFail($id);

        $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'company_name' => 'sometimes|required|string|max:255',
            'mobile' => ['sometimes', 'required', 'string', 'regex:/^09\d{9}$/', Rule::unique('receptors')->ignore($receptor->id)],
            'allowed_ip' => 'nullable|ip',
            'username' => ['sometimes', 'required', 'string', Rule::unique('receptors')->ignore($receptor->id)],
            'password' => 'sometimes|string|min:8',
        ]);

        $data = $request->only(['first_name', 'last_name', 'company_name', 'mobile', 'allowed_ip', 'username']);

        if ($request->has('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $receptor->update($data);

        return response()->json([
            'message' => 'Receptor updated successfully',
            'receptor' => $receptor->load('user'),
        ]);
    }

    public function destroy($id)
    {
        $receptor = Receptor::findOrFail($id);
        $receptor->delete();

        return response()->json([
            'message' => 'Receptor deleted successfully',
        ]);
    }
}

