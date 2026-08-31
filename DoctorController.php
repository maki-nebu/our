<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\Request;
use App\Models\Specialty;

class DoctorController extends Controller
{
    public function publicIndex()
    {
        $doctors = \App\Models\Doctor::all();
        $specialties = \App\Models\Specialty::all(); // if you have a specialties table

        return view('front.all_doctors', compact('doctors', 'specialties'));
    }

    public function index()
    {
        $doctors = Doctor::latest()->paginate(10);
        return view('admin.doctors.index', compact('doctors'));
    }

    public function create()
    {
        return view('admin.doctors.create');
    }

    public function store(Request $request)
    {
        // Validate input
        $data = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_am' => 'nullable|string|max:255',
            'speciality_en' => 'required|string|max:255',
            'speciality_am' => 'nullable|string|max:255',
            'availability_en' => 'nullable|string',
            'availability_am' => 'nullable|string',
            'email' => 'required|email|unique:doctors,email',
            'phone' => 'nullable|string|max:50',
            'description_en' => 'nullable|string',
            'description_am' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'status' => 'required|boolean',
            'facebook' => 'nullable|url',
            'twitter' => 'nullable|url',
            'instagram' => 'nullable|url',
            'linkedin' => 'nullable|url',
        ]);

        // Create doctor without image first
        $doctor = Doctor::create($data);

        // Handle image upload if exists
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->storeAs('public/doctors', $imageName);

            // Update doctor with image name
            $doctor->update(['image' => $imageName]);
        }

        return redirect()->route('admin.doctors.index')->with('success', 'Doctor created successfully');
    }

    public function edit($id)
    {
        $doctor = Doctor::findOrFail($id);
        return view('admin.doctors.edit', compact('doctor'));
    }

    public function update(Request $request, $id)
    {
        $doctor = Doctor::findOrFail($id);

        // Validate input
        $request->validate([
            'name_en' => 'required|string|max:255',
            'name_am' => 'required|string|max:255',
            'speciality_en' => 'required|string|max:255',
            'speciality_am' => 'required|string|max:255',
            'availability_en' => 'required|string|max:255',
            'availability_am' => 'required|string|max:255',
            'email' => 'required|email|unique:doctors,email,' . $doctor->id,
            'phone' => 'nullable|string|max:20',
            'description_en' => 'nullable|string',
            'description_am' => 'nullable|string',
            'status' => 'required|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'facebook' => 'nullable|url',
            'twitter' => 'nullable|url',
            'instagram' => 'nullable|url',
            'linkedin' => 'nullable|url',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $imageName = time() . '_' . $request->image->getClientOriginalName();
            $request->image->storeAs('public/doctors', $imageName);
            $doctor->image = $imageName;
        }

        // Update fields
        $doctor->name_en = $request->name_en;
        $doctor->name_am = $request->name_am;
        $doctor->speciality_en = $request->speciality_en;
        $doctor->speciality_am = $request->speciality_am;
        $doctor->availability_en = $request->availability_en;
        $doctor->availability_am = $request->availability_am;
        $doctor->email = $request->email;
        $doctor->phone = $request->phone;
        $doctor->description_en = $request->description_en;
        $doctor->description_am = $request->description_am;
        $doctor->status = $request->status;
        $doctor->facebook = $request->facebook;
        $doctor->twitter = $request->twitter;
        $doctor->instagram = $request->instagram;
        $doctor->linkedin = $request->linkedin;

        $doctor->save();

        return redirect()->route('admin.doctors.index')->with('success', 'Doctor updated successfully.');
    }

public function search(Request $request)
{
    $query = $request->search;
    $filter = $request->filter ?? 'name';

    $doctors = Doctor::query();

    if($filter === 'name') {
        $doctors->where('name_en', 'like', "%$query%")
                ->orWhere('name_am', 'like', "%$query%");
    } else if($filter === 'specialty') {
        $doctors->where('speciality_en', 'like', "%$query%")
                ->orWhere('speciality_am', 'like', "%$query%");
    }

    $doctors = $doctors->get();

    $specialties = Specialty::all();

    return view('front.all_doctors', compact('doctors', 'specialties'));
}


public function ajaxSearch(Request $request)
{
    $query = $request->get('query', '');
    $filter = $request->get('filter', 'name');

    if (empty($query)) {
        return response()->json([]);
    }

    $doctors = Doctor::query()
        ->when($filter === 'name', function($q) use ($query) {
            $q->where(function($q2) use ($query) {
                $q2->where('name_en', 'LIKE', $query.'%')
                   ->orWhere('name_am', 'LIKE', $query.'%');
            });
        })
        ->when($filter === 'specialty', function($q) use ($query) {
            $q->where(function($q2) use ($query) {
                $q2->where('speciality_en', 'LIKE', $query.'%')
                   ->orWhere('speciality_am', 'LIKE', $query.'%');
            });
        })
        ->where('status', 1)
        ->take(10)
        ->get(['id','name_en','name_am','speciality_en','speciality_am']); // only needed cols

    return response()->json($doctors);
}




    public function destroy($id)
    {
        $doctor = \App\Models\Doctor::findOrFail($id);

        // Delete the doctor
        $doctor->delete();

        // Redirect back with success message
        return redirect()->route('admin.doctors.index')
            ->with('success', 'Doctor deleted successfully.');
    }

    public function show($id)
{
    $doctor = Doctor::findOrFail($id);
    return view('front.doctor.show', compact('doctor'));
}

    
}
