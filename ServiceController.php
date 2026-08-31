<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Department;
use App\Models\Service;
use App\Models\Gallery;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    // Backend: List all services
    public function index()
    {
        $services = Service::with('department')->get();
        return view('admin.service.index', compact('services'));
    }

    // Backend: Create form
    public function create()
    {
        $departments = Department::where('is_active', 1)->get(); // fetch all active departments
        return view('admin.service.create', compact('departments'));
    }

    // Backend: Store new service
public function store(Request $request)
{
    $request->validate([
        'name_en' => 'required|string|max:255',
        'name_am' => 'required|string|max:255',
        'description_en' => 'required|string',
        'description_am' => 'required|string',
        'directorate_id' => 'nullable|exists:departments,id', // reference departments table
        'icon' => 'nullable|string|max:255',
        'image' => 'nullable|image|max:2048',
        'status' => 'required|boolean',
    ]);

    $data = $request->all();

    // Handle image upload
    if ($request->hasFile('image')) {
        $data['image'] = $request->file('image')->store('services', 'public');
    }

    // Optional: generate slug from English name
    $data['slug'] = \Str::slug($request->name_en);

    Service::create($data);

    return redirect()->route('admin.services.index')->with('success', 'Service created successfully.');
}


    // Backend: Edit form
    public function edit(Service $service)
    {
        $departments = Department::where('is_active', 1)->get(); // fetch all active departments
        $editDepartment = $service->directorate_id; // current department of the service

        return view('admin.service.edit', compact('service', 'departments', 'editDepartment'));
    }

    // Backend: Update service
    public function update(Request $request, Service $service)
{
   $request->validate([
    'name_en' => 'required|string|max:255',
    'name_am' => 'required|string|max:255',
    'description_en' => 'required|string',
    'description_am' => 'required|string',
    'directorate_id' => 'nullable|exists:departments,id', 
    'icon' => 'nullable|string|max:255',
    'image' => 'nullable|image|max:2048',
    'status' => 'required|boolean',
]);


    $data = $request->all();

    // Handle image upload
    if ($request->hasFile('image')) {
        $data['image'] = $request->file('image')->store('services', 'public');
    }

    $data['slug'] = Str::slug($request->name_en);

    $service->update($data);

    return redirect()->route('admin.services.index')->with('success', 'Service updated successfully.');
}


// Frontend: Show services grouped by department
public function frontend(Request $request)
{
    // get selected directorate_id from query
    $directorateId = $request->get('department_id'); // dropdown still sends "department_id"

    // fetch all active departments with their services
    $departments = \App\Models\Department::where('is_active', 1)
        ->with(['services' => function ($query) {
            $query->where('status', 1);
        }])
        ->get();

    // fetch services, optionally filtered by department
    $services = \App\Models\Service::where('status', 1)
        ->when($directorateId, function ($query, $directorateId) {
            $query->where('directorate_id', $directorateId);
        })
        ->get();

    // fetch general services (those without directorate)
    $generalServices = \App\Models\Service::where('status', 1)
        ->whereNull('directorate_id')
        ->get();

    return view('front.services', compact(
        'departments',
        'services',
        'generalServices',
        'directorateId'
    ));
}



    // Toggle service status
    public function toggleStatus($id)
    {
        $service = Service::findOrFail($id);
        $service->status = $service->status == 1 ? 0 : 1;
        $service->save();

        return redirect()->back()->with('success', 'Service status updated successfully.');
    }

    // Frontend: Show single service

public function show($id)
{
    $service = Service::with('department')->findOrFail($id);

    // Department related to the service
    $department = $service->department;

    // Galleries related to the department
    $galleries = $department 
        ? \App\Models\Gallery::where('department_id', $department->id)->get() 
        : \App\Models\Gallery::whereNull('department_id')->get();

    return view('front.services.show', compact('service', 'department', 'galleries'));
}




    // Frontend: Services grouped by department
    public function services()
    {
        $departments = Department::with(['services' => function($q) {
            $q->where('status', 1); // only active services
        }])
        ->where('is_active', 1)
        ->get();

        return view('front.services', compact('departments'));
    }
    public function getByDepartment($id)
{
    $services = Service::where('directorate_id', $id)
                        ->where('status', 1)
                        ->get();

    return response()->json($services);
}

public function destroy(Service $service)
{
    // Optional: delete image from storage if exists
    if ($service->image) {
        \Storage::disk('public')->delete($service->image);
    }

    $service->delete();

    return redirect()->route('admin.services.index')->with('success', 'Service deleted successfully.');
}


}
