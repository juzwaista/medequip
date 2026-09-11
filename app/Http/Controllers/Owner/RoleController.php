<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    /**
     * Display a listing of the roles scoped to the current distributor.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $distributorId = $user->role === 'distributor' ? $user->distributor->id : $user->distributor_id;
        
        // Only fetch roles that belong to this distributor
        $roles = Role::where('distributor_id', $distributorId)->with('permissions')->get();
        
        // For distributors, we only show permissions relevant to their shop operations
        // (We can filter permissions by a naming convention or a 'guard_name' if needed)
        // Assuming we prefix distributor permissions with 'shop.' e.g. 'shop.manage-orders'
        $permissions = Permission::where('name', 'like', 'shop.%')->get()->groupBy(function($permission) {
            $parts = explode('.', $permission->name);
            return isset($parts[1]) ? explode('-', $parts[1])[0] : 'other'; 
        });

        return Inertia::render('Owner/Roles/Index', [
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Store a newly created role in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $distributorId = $user->role === 'distributor' ? $user->distributor->id : $user->distributor_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles')->where(function ($query) use ($distributorId) {
                return $query->where('distributor_id', $distributorId);
            })],
            'description' => 'nullable|string|max:1000',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name'
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'distributor_id' => $distributorId,
        ]);

        if (!empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return redirect()->back()->with('success', 'Role created successfully.');
    }

    /**
     * Update the specified role in storage.
     */
    public function update(Request $request, Role $role)
    {
        $user = $request->user();
        $distributorId = $user->role === 'distributor' ? $user->distributor->id : $user->distributor_id;

        if ($role->distributor_id !== $distributorId) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles')->where(function ($query) use ($distributorId) {
                return $query->where('distributor_id', $distributorId);
            })->ignore($role->id)],
            'description' => 'nullable|string|max:1000',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name'
        ]);

        $role->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);
        
        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return redirect()->back()->with('success', 'Role updated successfully.');
    }

    /**
     * Remove the specified role from storage.
     */
    public function destroy(Request $request, Role $role)
    {
        $user = $request->user();
        $distributorId = $user->role === 'distributor' ? $user->distributor->id : $user->distributor_id;

        if ($role->distributor_id !== $distributorId) {
            abort(403, 'Unauthorized action.');
        }

        if ($role->name === 'Owner') {
            abort(403, 'Cannot delete the core Owner role.');
        }

        $role->delete();

        return redirect()->back()->with('success', 'Role deleted successfully.');
    }
}
