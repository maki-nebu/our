<?php

namespace App\Http\Controllers;

use App\Models\PublicationCategory;
use App\Models\Log;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PublicationCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:publicationcategory_access', ['only' => ['index', 'show']]);
        $this->middleware('permission:publicationcategory_create', ['only' => ['create', 'store']]);
        $this->middleware('permission:publicationcategory_edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:publicationcategory_delete', ['only' => ['destroy']]);
    }
    
    public function index(Request $request)
    {
        try {
            $categories = PublicationCategory::orderByDesc('updated_at')->get();
            return view('admin.publicationcategory.index', compact('categories'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function create()
    {
        try {
            return view('admin.publicationcategory.create');
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

                if (!file_exists('uploads/PublicationCategory')) {
                    mkdir('uploads/PublicationCategory', 0777, true);
                }
                $image->move('uploads/PublicationCategory', $imagename);
            } else {
                $imagename = "default.png";
            }
            $publicationcategory = PublicationCategory::create([
                'name' => $request->name,
                'image' => $imagename,
            ]);

            $publicationcategory->save();
            $log = new Log();
            $log->action = "A new event category created";
            $log->user_id = Auth::user()->id;
            $log->save();
            return redirect()->route('admin.publicationcategories')->with('successMsg', 'An Publication Category Successfully Saved!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }


    public function edit(Request $request, $id)
    {
        try {
            $category = PublicationCategory::find($id);
            return view('admin.publicationcategory.edit', compact('category'));
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
            $publicationcategory = PublicationCategory::find($id);
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $slug = Str::slug($request->name);
                if (isset($image)) {
                    $currentDate = Carbon::now()->toDateString();
                    $imagename = $slug . '-' . $currentDate . '-' . uniqid() . '.' . $image->getClientOriginalExtension();

                    if (!file_exists('uploads/PublicationCategory')) {
                        mkdir('uploads/PublicationCategory', 0777, true);
                    }
                    $image->move('uploads/PublicationCategory', $imagename);
                    $publicationcategory->image = $imagename;
                } else {
                    $imagename = "default.png";
                    $publicationcategory->image = $imagename;
                }
            }
            $publicationcategory->name = $request->name;

            $publicationcategory->save();
            $log = new Log();
            $log->action = "A  publication category information updated";
            $log->user_id = Auth::user()->id;
            $log->save();
            return redirect()->route('admin.publicationcategories')->with('successMsg', 'An Publication Category Updated Saved!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function status($id)
    {
        try {
            $item = PublicationCategory::findOrFail($id);
            $item->status = !$item->status;
            $item->save();
            $log = new Log();
            $log->action = "A Publication Category activated / deactivated";
            $log->user_id = Auth::user()->id;
            $log->save();
            return redirect()->back()->with('successMsg', 'Publication Category Activated / Deactivated!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function restore(int $id)
    {
        try {
            $category = PublicationCategory::onlyTrashed()->findOrFail($id);
            $category->restore();
            return redirect()->back()->with('successMsg', 'An Publication Category Restored!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function onlyTrashed()
    {
        try {
            $categories = PublicationCategory::onlyTrashed()->get();
            return view('admin.publicationcategory.trashed', compact('categories'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function permanent(Request $request, $id)
    {
        try {
            PublicationCategory::onlyTrashed()->find($id)->forceDelete();
            $log = new Log();
            $log->action = "An Publication Category deleted";
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
            $category = PublicationCategory::find($id);
            $category->delete();
            $log = new Log();
            $log->action = "An Publication Category Deleted";
            $log->user_id = Auth::user()->id;
            $log->save();
            return redirect()->back()->with('successMsg', 'Deleted Succesfully!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
}
