<?php

namespace App\Http\Controllers;

use App\Models\GalleryCategory;
use App\Models\Log;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class GalleryCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:gallerycategory_access', ['only' => ['index', 'show']]);
        $this->middleware('permission:gallerycategory_create', ['only' => ['create', 'store']]);
        $this->middleware('permission:gallerycategory_edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:gallerycategory_delete', ['only' => ['destroy', 'permanent', 'delete']]);
    }
    
    public function index(Request $request)
    {
        try {
            $categories = GalleryCategory::orderByDesc('updated_at')->get();
            return view('admin.gallerycategory.index', compact('categories'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function create()
    {
        try {
            return view('admin.gallerycategory.create');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function store(Request $request)
    {
        try {
            $this->validate($request, [
                'name' => 'required',
                'image' => 'required|mimes:jpeg,jpg,bmp,png',
            ]);
            $image = $request->file('image');
            $slug = Str::slug($request->name);

            if (isset($image)) {
                $currentDate = Carbon::now()->toDateString();
                $imagename = $slug . '-' . $currentDate . '-' . uniqid() . '.' . $image->getClientOriginalExtension();

                if (!file_exists('uploads/GalleryCategory')) {
                    mkdir('uploads/GalleryCategory', 0777, true);
                }
                $image->move('uploads/GalleryCategory', $imagename);
            } else {
                $imagename = "default.png";
            }
            $gallerycategory = GalleryCategory::create([
                'name' => $request->name,
                'image' => $imagename,
            ]);

            $gallerycategory->save();
            $log = new Log();
            $log->action = "A new  car category created";
            $log->user_id = Auth::user()->id;
            $log->save();
            return redirect()->route('admin.blogcategories')->with('successMsg', 'A  Gallery Category Successfully Saved!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }


    public function edit(Request $request, $id)
    {
        try {
            $category = GalleryCategory::find($id);
            return view('admin.gallerycategory.edit', compact('category'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function update(Request $request, $id)
    {
        try {
            $this->validate($request, [
                'name' => 'required',
                'image' => 'nullable|mimes:jpeg,jpg,bmp,png',
            ]);
            $gallerycategory = GalleryCategory::find($id);
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $slug = Str::slug($request->name);
                if (isset($image)) {
                    $currentDate = Carbon::now()->toDateString();
                    $imagename = $slug . '-' . $currentDate . '-' . uniqid() . '.' . $image->getClientOriginalExtension();

                    if (!file_exists('uploads/GalleryCategory')) {
                        mkdir('uploads/GalleryCategory', 0777, true);
                    }
                    $image->move('uploads/GalleryCategory', $imagename);
                    $gallerycategory->image = $imagename;
                } else {
                    $imagename = "default.png";
                    $gallerycategory->image = $imagename;
                }
            }
            $gallerycategory->name = $request->name;

            $gallerycategory->save();
            $log = new Log();
            $log->action = "A  car category information updated";
            $log->user_id = Auth::user()->id;
            $log->save();
            return redirect()->route('admin.blogcategories')->with('successMsg', 'A  Gallery Category Updated Saved!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function restore(int $id)
    {
        try {
            $category = GalleryCategory::onlyTrashed()->findOrFail($id);
            $category->restore();
            return redirect()->back()->with('successMsg', 'A Gallery Category Restored!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function status($id)
    {
        try {
            $item = GalleryCategory::findOrFail($id);
            $item->status = !$item->status;
            $item->save();
            $log = new Log();
            $log->action = "A Gallery Category activated / deactivated";
            $log->user_id = Auth::user()->id;
            $log->save();
            return redirect()->back()->with('successMsg', 'Gallery Category Activated / Deactivated!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function onlyTrashed()
    {
        try {
            $categories = GalleryCategory::onlyTrashed()->get();
            return view('admin.gallerycategory.trashed', compact('categories'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function permanent(Request $request, $id)
    {
        try {
            GalleryCategory::onlyTrashed()->find($id)->forceDelete();
            $log = new Log();
            $log->action = "A Gallery Category deleted";
            $log->user_id = Auth::user()->id;
            $log->save();
            return redirect()->back()->with('successMsg', 'Permanently Deleted Succesfully!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function Delete(Request $request, $id)
    {
        try {
            $gallerycategory = GalleryCategory::find($id);
            $gallerycategory->delete();
            $log = new Log();
            $log->action = "A  Gallery Category Deleted";
            $log->user_id = Auth::user()->id;
            $log->save();
            return redirect()->back()->with('successMsg', 'Deleted Succesfully!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
}
