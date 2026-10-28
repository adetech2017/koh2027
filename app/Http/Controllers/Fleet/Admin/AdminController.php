<?php

namespace App\Http\Controllers\Fleet\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fleet\AuditLog;
use App\Models\Fleet\FleetAdmin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Fleet admin accounts. Super admins only.
 */
class AdminController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => FleetAdmin::orderBy('name')->get()->map->toApi(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:fleet_admins,email'],
            'role' => ['required', Rule::in(FleetAdmin::ROLES)],
            'password' => ['required', 'string', 'min:10'],
        ]);

        $admin = FleetAdmin::create($data);
        AuditLog::record('admin.created', $admin, ['role' => $admin->role]);

        return response()->json(['admin' => $admin->toApi()], 201);
    }

    public function update(Request $request, FleetAdmin $admin): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('fleet_admins', 'email')->ignore($admin->id)],
            'role' => ['sometimes', 'required', Rule::in(FleetAdmin::ROLES)],
            'active' => ['sometimes', 'boolean'],
            'password' => ['nullable', 'string', 'min:10'],
        ]);

        $isSelf = $admin->is(auth('fleet_admin')->user());

        // Stop admins locking themselves (or everyone) out
        if ($isSelf && ((isset($data['role']) && $data['role'] !== $admin->role) || (isset($data['active']) && !$data['active']))) {
            throw ValidationException::withMessages(['role' => "You can't change your own role or suspend yourself."]);
        }

        $admin->fill(collect($data)->only(['name', 'email', 'role'])->all());

        if (!empty($data['password'])) {
            $admin->password = $data['password'];
        }
        if (array_key_exists('active', $data)) {
            $admin->deactivated_at = $data['active'] ? null : ($admin->deactivated_at ?? now());
        }

        $admin->save();
        AuditLog::record('admin.updated', $admin, [
            'fields' => array_keys(collect($data)->except('password')->all()),
            'password_reset' => !empty($data['password']),
        ]);

        return response()->json(['admin' => $admin->toApi()]);
    }
}
