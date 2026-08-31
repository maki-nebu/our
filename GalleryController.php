<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Setting;
use App\Models\Banner;
use App\Models\Gallery;
use App\Models\Log;
use App\Testimony;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\GalleryCategory;
use Illuminate\Support\Facades\Storage;




class GalleryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:gallery_access', ['only' => ['index']]);
        $this->middleware('permission:gallery_create', ['only' => ['create', 'store']]);
        $this->middleware('permission:gallery_edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:gallery_delete', ['only' => ['destroy']]);
    }

public function index()
{
    $galleries = Gallery::latest()->get();
    $categories = GalleryCategory::all();

    return view('admin.gallery.index', compact('galleries', 'categories'));
}

public function frontendIndex()
{
    $setting = Setting::first();

    // Fetch departments with their active galleries count
    $departments = Department::withCount(['galleries' => function($query) {
        $query->where('status', 1);
    }])->where('is_active', 1)->get();

    // Get distinct years from all active galleries
$years = Gallery::where('status', 1)
                ->whereNotNull('year')
                ->orderBy('year', 'asc')
                ->pluck('year')
                ->unique();


    // Fetch all active galleries (flat list)
    $galleries = Gallery::where('status', 1)->orderBy('created_at', 'asc')->get();
    
    // Total gallery count for "All Departments" filter
    $totalGalleryCount = $galleries->count();

    return view('front.gallery', compact('departments', 'setting', 'years', 'galleries', 'totalGalleryCount'));
}

public function frontendByDepartment($departmentId = null)
{
    $setting = Setting::first();
    
    // Fetch departments with their active galleries count
    $departments = Department::withCount(['galleries' => function($query) {
        $query->where('status', 1);
    }])->where('is_active', 1)->get();

    // Get distinct years from all active galleries
$years = Gallery::where('status', 1)
                ->whereNotNull('year')
                ->orderBy('year', 'asc')
                ->pluck('year')
                ->unique();

    // Fetch galleries based on department filter
    $galleriesQuery = Gallery::where('status', 1);
    
    if ($departmentId) {
        $galleriesQuery->where('department_id', $departmentId);
    }
    
    $galleries = $galleriesQuery->orderBy('created_at', 'asc')->get();
    $totalGalleryCount = Gallery::where('status', 1)->count();

    return view('front.gallery', compact('departments', 'setting', 'years', 'galleries', 'totalGalleryCount', 'departmentId'));
}
public function create()
{
    $departments = \App\Models\Department::all(); // fetch all active departments
    return view('admin.gallery.create', compact('departments'));
}

public function edit($id)
{
    $gallery = Gallery::findOrFail($id);
    $departments = \App\Models\Department::all();
    $categories = \App\Models\GalleryCategory::all(); // if you have categories

    return view('admin.gallery.edit', compact('gallery', 'departments', 'categories'));
}


public function store(Request $request)
{
    // Clear video_url if type is image
    if ($request->type === 'image') {
        $request->merge(['video_url' => null]);
    }

    $request->validate([
        'name' => 'required|string|max:255',
        'type' => 'required|in:image,video',
        'image' => 'nullable|image|max:2048',
        'video_url' => 'nullable|required_if:type,video|url',
        'status' => 'required|boolean',
        'department_id' => 'nullable|exists:departments,id',
        'year' => 'nullable|string|max:10', // added year validation
    ]);

    $departmentId = $request->department_id ?: null;
    $fileName = null;

    // Handle uploaded image (for photos or optional video thumbnail)
    if ($request->hasFile('image')) {
        $fileName = time() . '_' . $request->image->getClientOriginalName();
        $request->image->storeAs('galleries', $fileName, 'public');
    }

    // Create gallery record
    Gallery::create([
        'name' => $request->name,
        'department_id' => $departmentId,
        'type' => $request->type,
        'image' => $fileName, // image may be null for videos
        'video_url' => $request->type === 'video' ? $request->video_url : null,
        'status' => $request->status,
        'year' => $request->year, // save manually assigned year
    ]);

    return redirect()->route('admin.galleries.index')
                     ->with('success', 'Gallery created successfully!');
}


public function update(Request $request, $id)
{
    $gallery = Gallery::findOrFail($id);

    $request->validate([
        'name' => 'required|string|max:255',
        'type' => 'required|in:image,video',
        'image' => 'nullable|image|max:2048',
        'video_url' => 'nullable|url',
        'status' => 'required|boolean',
        'department_id' => 'nullable|exists:departments,id',
        'year' => 'nullable|string|max:10', // added year validation
    ]);

    $departmentId = $request->department_id ?: null;
    $gallery->name = $request->name;
    $gallery->department_id = $departmentId;
    $gallery->type = $request->type;
    $gallery->status = $request->status;

    // Handling image uploads
    if ($request->hasFile('image')) {
        $fileName = time() . '_' . $request->image->getClientOriginalName();
        $request->image->storeAs('galleries', $fileName, 'public');

        // Delete old image if exists
        if ($gallery->image && Storage::disk('public')->exists('galleries/'.$gallery->image)) {
            Storage::disk('public')->delete('galleries/'.$gallery->image);
        }

        $gallery->image = $fileName;

        // Clear video URL only if switching to image type
        if ($request->type === 'image') {
            $gallery->video_url = null;
        }
    }

    // Handling video galleries
    if ($request->type === 'video') {
        $gallery->video_url = $request->video_url;

        // Keep existing image if exists; optional: admin can upload a new thumbnail via image field
        // Do NOT clear $gallery->image if no new image uploaded
    }

    // Save manually assigned year
    $gallery->year = $request->year;

    $gallery->save();

    return redirect()->route('admin.galleries.index')->with('success', 'Gallery updated successfully!');
}


public function destroy($id)
{
    // Find the gallery
    $gallery = \App\Models\Gallery::findOrFail($id);

    // Delete the file from storage
    if ($gallery->file && Storage::disk('public')->exists($gallery->file)) {
        Storage::disk('public')->delete($gallery->file);
    }

    // Delete the database record
    $gallery->delete();

    return redirect()->route('admin.galleries.index')
        ->with('success', 'Gallery deleted successfully!');
}
    public function delete($id)
    {
        try {
            $gallery = Gallery::find($id);
            $gallery->delete();
            $log = new Log();
            $log->action = "A gallery deleted";
            $log->user_id = Auth::user()->id;
            $log->save();
            return redirect()->back()->with('successMsg', 'Gallery Successfully Deleted!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

public function frontendByYear($year)
{
    $setting = Setting::first();
    
    // Fetch departments with their active galleries count
    $departments = Department::withCount(['galleries' => function($query) {
        $query->where('status', 1);
    }])->where('is_active', 1)->get();

    // Fetch galleries based on year filter
$galleries = Gallery::where('status', 1)
                    ->where('year', $year)
                    ->orderBy('created_at', 'asc')
                    ->get();

                        
    $totalGalleryCount = Gallery::where('status', 1)->count();
$years = Gallery::where('status', 1)
                ->whereNotNull('year')
                ->orderBy('year', 'asc')
                ->pluck('year')
                ->unique();


    return view('front.gallery', compact('departments', 'setting', 'years', 'galleries', 'totalGalleryCount', 'year'));
}

public function frontendByType($type)
{
    $setting = Setting::first();

    $departments = Department::withCount(['galleries' => function($query) {
        $query->where('status', 1);
    }])->where('is_active', 1)->get();

    $years = Gallery::where('status', 1)
                    ->whereNotNull('year')
                    ->orderBy('year', 'asc')
                    ->pluck('year')
                    ->unique();

    $galleriesQuery = Gallery::where('status', 1);

    if ($type !== 'all') {
        $galleriesQuery->where('type', $type);
    }

    $galleries = $galleriesQuery->orderBy('created_at', 'asc')->get();

    $totalGalleryCount = Gallery::where('status', 1)->count();

    return view('front.gallery', compact('departments', 'setting', 'years', 'galleries', 'totalGalleryCount', 'type'));
}


public function filter(Request $request)
{
    $query = Gallery::where('status', 1);

    // Apply filters
    if ($request->department_id && $request->department_id !== 'all') {
        $query->where('department_id', (int)$request->department_id);
    }

    if ($request->year && $request->year !== 'all') {
        $query->where('year', $request->year);
    }

    if ($request->type && $request->type !== 'all') {
        $query->where('type', $request->type);
    }

    $galleries = $query->orderBy('created_at', 'desc')->get();

    // ✅ Recalculate counts for sidebar
    $deptCounts = Department::withCount(['galleries as galleries_count' => function ($q) use ($request) {
        $q->where('status', 1);

        if ($request->year && $request->year !== 'all') {
            $q->where('year', $request->year);
        }
        if ($request->type && $request->type !== 'all') {
            $q->where('type', $request->type);
        }
    }])->get();

    $yearCounts = Gallery::select('year')
        ->distinct()
        ->get()
        ->map(function ($row) use ($request) {
            $count = Gallery::where('status', 1)
                ->where('year', $row->year)
                ->when($request->department_id && $request->department_id !== 'all', function ($q) use ($request) {
                    $q->where('department_id', (int)$request->department_id);
                })
                ->when($request->type && $request->type !== 'all', function ($q) use ($request) {
                    $q->where('type', $request->type);
                })
                ->count();
            return [
                'year' => $row->year,
                'count' => $count,
            ];
        });

$typeCounts = collect([
    'image' => Gallery::where('status', 1)
        ->when($request->department_id && $request->department_id !== 'all', function ($q) use ($request) {
            $q->where('department_id', (int)$request->department_id);
        })
        ->when($request->year && $request->year !== 'all', function ($q) use ($request) {
            $q->where('year', $request->year);
        })
        ->where('type', 'image')
        ->count(),
    'video' => Gallery::where('status', 1)
        ->when($request->department_id && $request->department_id !== 'all', function ($q) use ($request) {
            $q->where('department_id', (int)$request->department_id);
        })
        ->when($request->year && $request->year !== 'all', function ($q) use ($request) {
            $q->where('year', $request->year);
        })
        ->where('type', 'video')
        ->count(),
]);
    $html = view('front.partials.gallery_items', compact('galleries'))->render();

    return response()->json([
        'html' => $html,
        'deptCounts' => $deptCounts,
        'yearCounts' => $yearCounts,
        'typeCounts' => $typeCounts,
        'totalCount' => $galleries->count(),
    ]);
}
}
