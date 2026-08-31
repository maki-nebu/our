<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Testimony;
use App\Models\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TestimonyController extends Controller
{
    // Middleware for permissions (optional, adjust if you have roles)
    public function __construct()
    {
        $this->middleware(['auth:admin,web', 'set_guard:admin,web']);
        // Example permission middleware
        // $this->middleware('permission:testimony_access', ['only' => ['index']]);
        // $this->middleware('permission:testimony_create', ['only' => ['create','store']]);
        // $this->middleware('permission:testimony_edit', ['only' => ['edit','update','enable']]);
        // $this->middleware('permission:testimony_delete', ['only' => ['destroy']]);
    }

    // Show all testimonies
    public function index()
    {
        $testimonies = Testimony::orderBy('updated_at', 'desc')->get();
        return view('admin.testimony.index', compact('testimonies'));
    }

    // Show create form
    public function create()
    {
        return view('admin.testimony.create');
    }

    // Store new testimony


public function store(Request $request)
{
    $request->validate([
        'name_en' => 'required|string|max:255',
        'name_am' => 'required|string|max:255',
        'description_en' => 'nullable|string',
        'description_am' => 'nullable|string',
        'photo_url' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'position' => 'nullable|string|max:255',
        'is_active' => 'required|boolean',
    ]);

    $testimony = new Testimony();
    $testimony->name_en = $request->name_en;
    $testimony->name_am = $request->name_am;
    $testimony->description_en = $request->description_en;
    $testimony->description_am = $request->description_am;
    $testimony->position = $request->position;
    $testimony->is_active = $request->is_active;

    // Handle photo upload using storage
    if ($request->hasFile('photo_url')) {
        $path = $request->file('photo_url')->store('testimonies', 'public'); 
        // stores in storage/app/public/testimonies
        $testimony->photo_url = $path; 
    }

    $testimony->save();

    // Log action
    $log = new Log();
    $log->action = "Added a new testimony";
    $log->user_id = Auth::user()->id;
    $log->save();

    return redirect()->route('admin.testimonies.index')->with('successMsg', 'Testimony created successfully.');
}

    // Update existing testimony
    public function update(Request $request, $id)
{
    $testimony = Testimony::findOrFail($id);

    $request->validate([
        'name_en' => 'required|string|max:255',
        'name_am' => 'required|string|max:255',
        'description_en' => 'nullable|string',
        'description_am' => 'nullable|string',
        'photo_url' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'position' => 'nullable|string|max:255',
        'is_active' => 'required|boolean',
    ]);

    $testimony->name_en = $request->name_en;
    $testimony->name_am = $request->name_am;
    $testimony->description_en = $request->description_en;
    $testimony->description_am = $request->description_am;
    $testimony->position = $request->position;
    $testimony->is_active = $request->is_active;

    // Handle photo update
    if ($request->hasFile('photo_url')) {
        // Delete old photo if exists
        if ($testimony->photo_url && Storage::disk('public')->exists($testimony->photo_url)) {
            Storage::disk('public')->delete($testimony->photo_url);
        }

        $path = $request->file('photo_url')->store('testimonies', 'public');
        $testimony->photo_url = $path;
    }

    $testimony->save();

    // Log action
    $log = new Log();
    $log->action = "Updated a testimony";
    $log->user_id = Auth::user()->id;
    $log->save();

    return redirect()->route('admin.testimonies.index')->with('successMsg', 'Testimony updated successfully.');
}

    // Show edit form
    public function edit($id)
{
    $testimonial = \App\Models\Testimony::find($id);

    if (!$testimonial) {
        return redirect()->route('admin.testimonies.index')
                         ->with('error', 'Testimony not found.');
    }

    return view('admin.testimony.edit', compact('testimonial'));
}


    // Enable or disable testimony
    public function enable($id)
    {
        try {
            $testimony = Testimony::findOrFail($id);
            $testimony->is_active = !$testimony->is_active;
            $testimony->save();

            $log = new Log();
            $log->action = $testimony->is_active ? "Enabled a testimony" : "Disabled a testimony";
            $log->user_id = Auth::user()->id;
            $log->save();

            return redirect()->route('admin.testimonies.index')->with('successMsg', 'Testimony status updated successfully.');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    // Delete testimony
    public function destroy($id)
    {
        $testimony = Testimony::findOrFail($id);

        // Delete photo
        if ($testimony->photo_url && file_exists(public_path('uploads/Testimony/'.$testimony->photo_url))) {
            unlink(public_path('uploads/Testimony/'.$testimony->photo_url));
        }

        $testimony->delete();

        // Log action
        $log = new Log();
        $log->action = "Deleted a testimony";
        $log->user_id = Auth::user()->id;
        $log->save();

        return redirect()->route('admin.testimonies.index')->with('successMsg', 'Testimony deleted successfully.');
    }
}
