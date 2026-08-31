<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Log;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DepartmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:department_access')->only('index', 'show');
        $this->middleware('can:department_create')->only('create', 'store');
        $this->middleware('can:department_edit')->only('edit', 'update');
        $this->middleware('can:department_delete')->only('destroy');
    }

    // List all departments
    public function index()
    {
        $departments = Department::orderBy('created_at', 'desc')->paginate(10);
        return view('admin.department.index', compact('departments'));
    }

    // Show create form
    public function create()
    {
        return view('admin.department.create');
    }

    // Store new department
public function store(Request $request)
{
    // Validation
    $request->validate([
        'department_name' => 'required|string|max:255',
        'department_name_am' => 'nullable|string|max:255',
        'description' => 'required|string',
        'description_am' => 'nullable|string',
        'director_name' => 'required|string|max:255',
        'director_name_am' => 'nullable|string|max:255',
        'vice_director' => 'required|string|max:255',
        'vice_director_am' => 'nullable|string|max:255',
        'director_photo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        'vice_director_photo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        'department_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        'icon' => 'nullable|string|max:255', // now text input
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        'slug' => 'nullable|string|max:255',
        'is_active' => 'required|boolean',
    ]);

    // Create new department
    $department = new Department();
    $department->department_name = $request->department_name;
    $department->department_name_am = $request->department_name_am;
    $department->slug = $request->slug ?? Str::slug($request->department_name);
    $department->description = $request->description;
    $department->description_am = $request->description_am;
    $department->director_name = $request->director_name;
    $department->director_name_am = $request->director_name_am;
    $department->vice_director = $request->vice_director;
    $department->vice_director_am = $request->vice_director_am;
    $department->icon = $request->icon;
    $department->is_active = $request->is_active;

    // Handle file uploads
    if ($request->hasFile('director_photo')) {
        $department->director_photo = $request->file('director_photo')->store('departments', 'public');
    }
    if ($request->hasFile('vice_director_photo')) {
        $department->vice_director_photo = $request->file('vice_director_photo')->store('departments', 'public');
    }
    if ($request->hasFile('department_photo')) {
        $department->department_photo = $request->file('department_photo')->store('departments', 'public');
    }
    if ($request->hasFile('image')) {
        $department->image = $request->file('image')->store('departments', 'public');
    }

    $department->save();

    return redirect()->route('admin.departments.index')->with('success', 'Department created successfully.');
}


    // Show edit form
    public function edit($id)
    {
        $department = Department::findOrFail($id);
        return view('admin.department.edit', compact('department'));
    }

  public function update(Request $request, $id)
{
    // Validation
    $request->validate([
        'department_name' => 'required|string|max:255',
        'department_name_am' => 'required|string|max:255',
        'description' => 'required|string',
        'description_am' => 'required|string',
        'director_name' => 'required|string|max:255',
        'director_name_am' => 'required|string|max:255',
        'vice_director' => 'required|string|max:255',
        'vice_director_am' => 'required|string|max:255',
        'icon' => 'nullable|string', // icon is text now
        'director_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp',
        'vice_director_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp',
        'department_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp',
        'is_active' => 'required|boolean',
    ]);

    // Find department
    $department = Department::findOrFail($id);

    // Update fields
    $department->department_name = $request->department_name;
    $department->department_name_am = $request->department_name_am;
    $department->slug = $request->slug ?? Str::slug($request->department_name);
    $department->description = $request->description;
    $department->description_am = $request->description_am;
    $department->director_name = $request->director_name;
    $department->director_name_am = $request->director_name_am;
    $department->vice_director = $request->vice_director;
    $department->vice_director_am = $request->vice_director_am;
    $department->icon = $request->icon;
    $department->is_active = $request->is_active;

    // Handle file uploads
    if ($request->hasFile('director_photo')) {
        $department->director_photo = $request->file('director_photo')->store('departments', 'public');
    }
    if ($request->hasFile('vice_director_photo')) {
        $department->vice_director_photo = $request->file('vice_director_photo')->store('departments', 'public');
    }
    if ($request->hasFile('department_photo')) {
        $department->department_photo = $request->file('department_photo')->store('departments', 'public');
    }
    if ($request->hasFile('image')) {
        $department->image = $request->file('image')->store('departments', 'public');
    }

    $department->save();

    return redirect()->route('admin.departments.index')->with('success', 'Department updated successfully.');
}

    // Toggle active status
    public function toggleStatus($id)
    {
        $department = Department::findOrFail($id);
        $department->is_active = !$department->is_active;
        $department->save();

        $this->logAction('Toggled department status ID ' . $id);

        return redirect()->back()->with('successMsg', 'Department status updated.');
    }

    // Delete department
    public function destroy($id)
    {
        $department = Department::findOrFail($id);
        $department->delete();

        $this->logAction('Deleted department ID ' . $id);

        return redirect()->route('admin.departments.index')->with('successMsg', 'Department deleted successfully.');
    }

    // Private helper for image upload
    private function uploadImage($file, $name)
    {
        $slug = Str::slug($name);
        $currentDate = Carbon::now()->toDateString();
        $filename = $slug . '-' . $currentDate . '-' . uniqid() . '.' . $file->getClientOriginalExtension();

        if (!file_exists('uploads/Department')) {
            mkdir('uploads/Department', 0777, true);
        }

        $file->move('uploads/Department', $filename);
        return $filename;
    }

    // Private helper to log actions
    private function logAction($action)
    {
        $log = new Log();
        $log->action = $action;
        $log->user_id = Auth::user()->id;
        $log->save();
    }
}
