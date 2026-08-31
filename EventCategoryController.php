<?php

namespace App\Http\Controllers;

use App\Models\EventCategory;
use App\Models\Log;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class EventCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:eventcategory_access', ['only' => ['index', 'show']]);
        $this->middleware('permission:eventcategory_create', ['only' => ['create', 'store']]);
        $this->middleware('permission:eventcategory_edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:eventcategory_delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        try {
            $categories = EventCategory::orderByDesc('updated_at')->get();
            return view('admin.eventcategory.index', compact('categories'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function create()
    {
        try {
            return view('admin.eventcategory.create');
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

                if (!file_exists('uploads/EventCategory')) {
                    mkdir('uploads/EventCategory', 0777, true);
                }
                $image->move('uploads/EventCategory', $imagename);
            } else {
                $imagename = "default.png";
            }
            $eventcategory = EventCategory::create([
                'name' => $request->name,
                'image' => $imagename,
            ]);

            $eventcategory->save();
            $log = new Log();
            $log->action = "A new event category created";
            $log->user_id = Auth::user()->id;
            $log->save();
            return redirect()->route('admin.eventcategories')->with('successMsg', 'An Event Category Successfully Saved!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }


    public function edit(Request $request, $id)
    {
        try {
            $category = EventCategory::find($id);
            return view('admin.eventcategory.edit', compact('category'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function update(Request $request, $id)
    {
        try {
            $this->validate($request, [
                'name' => 'required'
            ]);
            $eventcategory = EventCategory::find($id);
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $slug = Str::slug($request->name);
                if (isset($image)) {
                    $currentDate = Carbon::now()->toDateString();
                    $imagename = $slug . '-' . $currentDate . '-' . uniqid() . '.' . $image->getClientOriginalExtension();

                    if (!file_exists('uploads/EventCategory')) {
                        mkdir('uploads/EventCategory', 0777, true);
                    }
                    $image->move('uploads/EventCategory', $imagename);
                    $eventcategory->image = $imagename;
                } else {
                    $imagename = "default.png";
                    $eventcategory->image = $imagename;
                }
            }
            $eventcategory->name = $request->name;

            $eventcategory->save();
            $log = new Log();
            $log->action = "A  car category information updated";
            $log->user_id = Auth::user()->id;
            $log->save();
            return redirect()->route('admin.eventcategories')->with('successMsg', 'An Event Category Updated Saved!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function status($id)
    {
        try {
            $item = EventCategory::findOrFail($id);
            $item->status = !$item->status;
            $item->save();
            $log = new Log();
            $log->action = "An Event Category activated / deactivated";
            $log->user_id = Auth::user()->id;
            $log->save();
            return redirect()->back()->with('successMsg', 'Event Category Activated / Deactivated!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function restore(int $id)
    {
        try {
            $gallery = EventCategory::onlyTrashed()->findOrFail($id);
            $gallery->restore();
            return redirect()->back()->with('successMsg', 'An Event Category Restored!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function onlyTrashed()
    {
        try {
            $categories = EventCategory::onlyTrashed()->get();
            return view('admin.eventcategory.trashed', compact('categories'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function permanent(Request $request, $id)
    {
        try {
            EventCategory::onlyTrashed()->find($id)->forceDelete();
            $log = new Log();
            $log->action = "An Event Category deleted";
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
            $eventcategory = EventCategory::find($id);
            $eventcategory->delete();
            $log = new Log();
            $log->action = "An Event Category Deleted";
            $log->user_id = Auth::user()->id;
            $log->save();
            return redirect()->back()->with('successMsg', 'Deleted Succesfully!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
}
