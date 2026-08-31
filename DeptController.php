<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Department;
use App\Models\Service;
use App\Models\Facility;
use App\Models\Gallery;
use App\Models\Setting;


class DeptController extends Controller
{
    public function index()
    {
        // Get all active departments
        $departments = Department::where('is_active', true)->get();
        
        // Get all active services
        $services = Service::where('status', true)->get();
        
        return view('front.departments', compact('departments', 'services'));
    }
    
public function show($slug)
{
    // Get the department by slug
    $department = Department::where('slug', $slug)
                            ->where('is_active', true)
                            ->firstOrFail();

    // Get services for this department
    $services = Service::where('directorate_id', $department->id)
                        ->where('status', true)
                        ->get();

    // Get all facilities
    $facilities = Facility::all();

    // Get galleries for this department
    $galleries = Gallery::where('department_id', $department->id)
                        ->where('status', true)
                        ->get();

     $settings = Setting::first();

    return view('front.department_detail', compact('department', 'services', 'facilities', 'galleries', 'settings'));
}


}