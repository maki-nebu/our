<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use DB;

class RoleController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:role_access', ['only' => ['index']]);
        $this->middleware('permission:role_create', ['only' => ['create', 'store']]);
        $this->middleware('permission:role_edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:role_delete', ['only' => ['destroy']]);
    }

    public function index()
    {
        $roles = Role::orderByDesc('id')->paginate(10);
        return view('admin.roles.index', compact('roles'));
    }

public function create()
{
    $roles = Role::all(); // fetch all roles from database
    $permissions = Permission::all(); // if you need permissions in the form

    return view('admin.roles.create', compact('roles', 'permissions'));
}

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
            'permissions' => 'nullable|array'
        ]);

        $role = Role::create(['name' => $request->name]);
        $role->syncPermissions($request->permissions);

        return redirect()->route('admin.roles.index')->with('success', 'Role created successfully.');
    }

public function edit($id)
{
    $role = Role::findOrFail($id);
    $permissions = Permission::all();

   $rolePermissions = $role->permissions->pluck('id')->toArray();

    return view('admin.roles.edit', compact('role', 'permissions', 'rolePermissions'));
}

public function update(Request $request, $id)
{
    $role = Role::findOrFail($id);

    $request->validate([
        'name' => 'required|unique:roles,name,' . $id,
        'permission' => 'nullable|array',
        'permission.*' => 'exists:permissions,id',
    ]);

    // Update role name
    $role->name = $request->name;
    $role->save();

    // Fetch permission models and sync
    $permissions = Permission::whereIn('id', $request->permission ?? [])->get();
    $role->syncPermissions($permissions);

    return redirect()->route('admin.roles.index')
                     ->with('success', 'Role updated successfully with assigned permissions.');
}




    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        $role->delete();

        // Optional: remove role from users
        DB::table('model_has_roles')->where('role_id', $id)->delete();

        return redirect()->route('admin.roles.index')->with('success', 'Role deleted successfully.');
    }
}
