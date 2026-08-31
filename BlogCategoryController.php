<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\Log;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class BlogCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:blogcategory_access', ['only' => ['index', 'show','index']]);
        $this->middleware('permission:blogcategory_create', ['only' => ['create', 'store','index']]);
        $this->middleware('permission:blogcategory_edit', ['only' => ['edit', 'update','index']]);
        $this->middleware('permission:blogcategory_delete', ['only' => ['destroy']]);
    }


    public function index(Request $request)
    {
        try {
            $categories = BlogCategory::orderByDesc('updated_at')->get();
            return view('admin.blogcategory.index', compact('categories'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function create()
    {
        try {
            return view('admin.blogcategory.create');
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

                if (!file_exists('uploads/BlogCategory')) {
                    mkdir('uploads/BlogCategory', 0777, true);
                }
                $image->move('uploads/BlogCategory', $imagename);
            } else {
                $imagename = "default.png";
            }
            $blogcategory = BlogCategory::create([
                'name' => $request->name,
                'image' => $imagename,
            ]);

            $blogcategory->save();
            $log = new Log();
            $log->action = "A new  car category created";
            $log->user_id = Auth::user()->id;
            $log->save();
            return redirect()->route('admin.blogcategories')->with('successMsg', 'A  Blog Category Successfully Saved!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }


    public function edit(Request $request, $id)
    {
        try {
            $category = BlogCategory::find($id);
            return view('admin.blogcategory.edit', compact('category'));
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
            $blogcategory = BlogCategory::find($id);
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $slug = Str::slug($request->name);
                if (isset($image)) {
                    $currentDate = Carbon::now()->toDateString();
                    $imagename = $slug . '-' . $currentDate . '-' . uniqid() . '.' . $image->getClientOriginalExtension();

                    if (!file_exists('uploads/BlogCategory')) {
                        mkdir('uploads/BlogCategory', 0777, true);
                    }
                    $image->move('uploads/BlogCategory', $imagename);
                    $blogcategory->image = $imagename;
                } else {
                    $imagename = "default.png";
                    $blogcategory->image = $imagename;
                }
            }
            $blogcategory->name = $request->name;

            $blogcategory->save();
            $log = new Log();
            $log->action = "A  car category information updated";
            $log->user_id = Auth::user()->id;
            $log->save();
            return redirect()->route('admin.blogcategories')->with('successMsg', 'A  Blog Category Updated Saved!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function restore(int $id)
    {
        try {
            $category = BlogCategory::onlyTrashed()->findOrFail($id);
            $category->restore();
            return redirect()->back()->with('successMsg', 'A Blog Category Restored!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function status($id)
    {
        try {
            $item = BlogCategory::findOrFail($id);
            $item->status = !$item->status;
            $item->save();
            $log = new Log();
            $log->action = "A BlogCategory activated / deactivated";
            $log->user_id = Auth::user()->id;
            $log->save();
            return redirect()->back()->with('successMsg', 'Blog Category Activated / Deactivated!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function onlyTrashed()
    {
        try {
            $categories = BlogCategory::onlyTrashed()->get();
            return view('admin.blogcategory.trashed', compact('categories'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function permanent(Request $request, $id)
    {
        try {
            BlogCategory::onlyTrashed()->find($id)->forceDelete();
            $log = new Log();
            $log->action = "A Blog Category deleted";
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
            $blogcategory = BlogCategory::find($id);
            $blogcategory->delete();
            $log = new Log();
            $log->action = "A  Blog Category Deleted";
            $log->user_id = Auth::user()->id;
            $log->save();
            return redirect()->back()->with('successMsg', 'Deleted Succesfully!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
}
