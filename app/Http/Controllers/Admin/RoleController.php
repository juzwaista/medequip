<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    /**
     * Display a listing of the platform roles.
     */
    public function index()
    {
        // For platform admins, we only want roles where distributor_id is null
        $roles = Role::whereNull('distributor_id')->with('permissions')->get();

        // Only expose admin.* permissions to the UI — group by functional area
        // e.g. 'admin.applications.review' → group 'applications', label 'review'
        $permissions = Permission::where('name', 'like', 'admin.%')
            ->get()
            ->groupBy(function ($permission) {
                $parts = explode('.', $permission->name); // ['admin', 'applications', 'review']
                return $parts[1] ?? 'other'; // Group key: 'applications'
            });

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Store a newly created role in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles')->whereNull('distributor_id')],
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name'
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'distributor_id' => null, // Platform scope
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
        if ($role->distributor_id !== null) {
            abort(403, 'Cannot edit distributor roles from platform admin.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles')->whereNull('distributor_id')->ignore($role->id)],
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name'
        ]);

        $role->update(['name' => $validated['name']]);
        
        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return redirect()->back()->with('success', 'Role updated successfully.');
    }

    /**
     * Remove the specified role from storage.
     */
    public function destroy(Role $role)
    {
        if ($role->distributor_id !== null) {
            abort(403, 'Cannot delete distributor roles from platform admin.');
        }

        if (in_array($role->name, ['Super Admin'])) {
            abort(403, 'Cannot delete core system roles.');
        }

        $role->delete();

        return redirect()->back()->with('success', 'Role deleted successfully.');
    }
}
