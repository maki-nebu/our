<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

use Illuminate\Http\Request;
use App\Models\Facility;

class FacilitiesController extends Controller
{
    // Display all facilities (paginated)
    public function index()
    {
        $facilities = Facility::paginate(9); // 9 per page, adjust as needed
        return view('front.facilities', compact('facilities'));
    }


    // ================= ADMIN METHODS =================

// Admin index - list all facilities
public function adminIndex()
{
    $facilities = Facility::latest()->paginate(10);
    return view('admin.facilities.index', compact('facilities'));
}

// Show create form
public function adminCreate()
{
    return view('admin.facilities.create');
}

// Store new facility
public function adminStore(Request $request)
{
    $data = $request->validate([
        'name_en' => 'required|string|max:255',
        'name_am' => 'required|string|max:255',
        'description_en' => 'required|string',
        'description_am' => 'required|string',
        'image' => 'nullable|image|mimes:jpg,png,jpeg,gif,webp|max:2048',
    ]);

    if ($request->hasFile('image')) {
        $data['image'] = $request->file('image')->store('facilities', 'public');
    }

    Facility::create($data);

    return redirect()->route('admin.facilities.index')->with('success', 'Facility created successfully.');
}

// Show edit form
public function adminEdit(Facility $facility)
{
    return view('admin.facilities.edit', compact('facility'));
}

// Update existing facility
public function adminUpdate(Request $request, Facility $facility)
{
    $data = $request->validate([
        'name_en' => 'required|string|max:255',
        'name_am' => 'required|string|max:255',
        'description_en' => 'required|string',
        'description_am' => 'required|string',
        'image' => 'nullable|image|mimes:jpg,png,jpeg,gif,webp|max:2048',
    ]);

    if ($request->hasFile('image')) {
        // Delete old image
        if ($facility->image) {
            Storage::disk('public')->delete($facility->image);
        }
        $data['image'] = $request->file('image')->store('facilities', 'public');
    }

    $facility->update($data);

    return redirect()->route('admin.facilities.index')->with('success', 'Facility updated successfully.');
}

// Delete facility
public function adminDestroy(Facility $facility)
{
    if ($facility->image) {
        Storage::disk('public')->delete($facility->image);
    }

    $facility->delete();

    return redirect()->route('admin.facilities.index')->with('success', 'Facility deleted successfully.');
}


}
