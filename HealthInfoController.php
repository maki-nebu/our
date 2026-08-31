<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HealthInfo;
use App\Models\HealthInfoCategory;
use App\Models\Faq;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HealthInfoController extends Controller
{

public function index(Request $request)
{
    $query = HealthInfo::with('category')->where('is_active', true);

    if ($request->has('category')) {
        $query->where('category_id', $request->category);
    }

    // Get all items with pagination for better performance
    $healthInfos = $query->orderBy('created_at', 'desc')->paginate(20);
    $categories = HealthInfoCategory::select('id', 'name_en', 'name_am')->get();
    $faqs = Faq::select('id', 'question_en', 'question_am', 'answer_en', 'answer_am')->get();

    return view('front.health_info', compact('healthInfos', 'categories', 'faqs'));
}

public function preview($id)
{
    $healthInfo = HealthInfo::findOrFail($id);

    if (!$healthInfo->file_path || !Storage::exists('public/' . $healthInfo->file_path)) {
        abort(404, 'File not found.');
    }

    // Fetch related infos in the same category
    $relatedInfos = HealthInfo::where('category_id', $healthInfo->category_id)
        ->where('id', '!=', $healthInfo->id)
        ->take(6)
        ->get();

    return view('front.health.preview', compact('healthInfo', 'relatedInfos'));
}




public function download($id)
{
    $healthInfo = HealthInfo::findOrFail($id);

    if (!$healthInfo->file_path || !Storage::disk('public')->exists($healthInfo->file_path)) {
        abort(404, 'File not found.');
    }

    // Increment download count
    $healthInfo->increment('download_count');

    $filePath = storage_path('app/public/' . $healthInfo->file_path);

    // Force download with a nice filename
    $downloadName = ($healthInfo->title_en ?? 'document') . '.' . pathinfo($filePath, PATHINFO_EXTENSION);

    return response()->download($filePath, $downloadName);
}

    // Admin CRUD methods
public function adminIndex()
{
    $healthInfos = HealthInfo::orderBy('created_at', 'desc')->paginate(10);
    return view('admin.health_info.index', compact('healthInfos'));
}


public function create()
{
    $categories = HealthInfoCategory::all(); // full objects with id, name_en, name_am
    return view('admin.health_info.create', compact('categories'));
}


public function store(Request $request)
{
    $request->validate([
        'title_en' => 'required|string|max:255',
        'title_am' => 'required|string|max:255',
        'category_id' => 'required|exists:health_info_category,id',
        'description_en' => 'required|string',
        'description_am' => 'required|string',
        'is_active' => 'boolean',
    ]);

    $data = $request->all();

    // Handle file upload...
    if ($request->hasFile('file_path')) {
        $file = $request->file('file_path');
        $fileName = 'health_info/' . time() . '_' . \Str::slug($request->title_en) . '.' . $file->getClientOriginalExtension();
        $file->storeAs('public', $fileName);
        $data['file_path'] = $fileName;
        $data['file_size'] = $file->getSize();
        $data['file_type'] = $file->getClientMimeType();
    }

    // Handle thumbnail...
    if ($request->hasFile('thumbnail_path')) {
        $thumbnail = $request->file('thumbnail_path');
        $thumbnailName = 'health_info/thumbnails/' . time() . '_' . \Str::slug($request->title_en) . '.' . $thumbnail->getClientOriginalExtension();
        $thumbnail->storeAs('public', $thumbnailName);
        $data['thumbnail_path'] = $thumbnailName;
    }

    // ✅ Ensure category_id is saved
    $data['category_id'] = $request->category_id;

    HealthInfo::create($data);

    return redirect()->route('admin.health-info.index')
                     ->with('success', 'Health information created successfully.');
}


public function edit($id)
{
    $healthInfo = HealthInfo::findOrFail($id);
    $categories = HealthInfoCategory::all();
    return view('admin.health_info.edit', compact('healthInfo', 'categories'));
}



public function update(Request $request, $id)
{
    $healthInfo = HealthInfo::findOrFail($id);

    $request->validate([
        'title_en' => 'required|string|max:255',
        'title_am' => 'required|string|max:255',
        'description_en' => 'required|string',
        'description_am' => 'required|string',
        'category_id' => 'required|exists:health_info_category,id',
        'file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
        'is_active' => 'sometimes|boolean',
    ]);

    $data = $request->except(['file', 'thumbnail']);
    $data['category_id'] = $request->category_id;
    $data['is_active'] = $request->has('is_active');

    // Handle file upload
if ($request->hasFile('file_path')) {
    if ($healthInfo->file_path) {
        Storage::delete('public/' . $healthInfo->file_path);
    }

    $file = $request->file('file_path');
    $fileName = 'health_info/' . time() . '_' . Str::slug($request->title_en) . '.' . $file->getClientOriginalExtension();
    $file->storeAs('public', $fileName);
    $data['file_path'] = $fileName;
    $data['file_size'] = $file->getSize();
    $data['file_type'] = $file->getClientMimeType();
}


    // Handle thumbnail upload
    if ($request->hasFile('thumbnail')) {
        if ($healthInfo->thumbnail_path) {
            Storage::delete('public/' . $healthInfo->thumbnail_path);
        }

        $thumbnail = $request->file('thumbnail');
        $thumbnailName = 'health_info/thumbnails/' . time() . '_' . Str::slug($request->title_en) . '.' . $thumbnail->getClientOriginalExtension();
        $thumbnail->storeAs('public', $thumbnailName);
        $data['thumbnail_path'] = $thumbnailName;
    }

    $healthInfo->update($data);

    return redirect()->route('admin.health-info.index')
        ->with('success', 'Health information updated successfully.');
}


    public function destroy($id)
    {
        $healthInfo = HealthInfo::findOrFail($id);
        
        // Delete associated files
        if ($healthInfo->file_path) {
            Storage::delete('public/' . $healthInfo->file_path);
        }
        
        if ($healthInfo->thumbnail_path) {
            Storage::delete('public/' . $healthInfo->thumbnail_path);
        }
        
        $healthInfo->delete();

        return redirect()->route('admin.health-info.index')
            ->with('success', 'Health information deleted successfully.');
    }

    public function toggleStatus($id)
    {
        $healthInfo = HealthInfo::findOrFail($id);
        $healthInfo->is_active = !$healthInfo->is_active;
        $healthInfo->save();

        return redirect()->route('admin.healthinfo.index')
            ->with('success', 'Status updated successfully.');
    }
}