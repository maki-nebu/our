<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\History;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class HistoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:history_access', ['only' => ['index', 'show']]);
        $this->middleware('permission:history_create', ['only' => ['create', 'store']]);
        $this->middleware('permission:history_edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:history_delete', ['only' => ['destroy']]);
    }
    
    public function index()
    {
        try {
            $histories = History::orderBy('year', 'desc')->get();
            return view('admin.history.index', compact('histories'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function create()
    {
        try {
            return view('admin.history.create');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function edit($id)
    {
        try {
            $history = History::find($id);
            return view('admin.history.edit', compact('history'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function store(Request $request)
    {
        try {
            $this->validate($request, [
                'title' => 'required',
                'year' => 'required',
                'description' => 'required',
                'image' => 'required|mimes:jpeg,jpg,bmp,png',
            ]);
            $image = $request->file('image');
            $slug = Str::slug($request->title);
            if (isset($image)) {
                $currentDate = Carbon::now()->toDateString();
                $imagename = $slug . '-' . $currentDate . '-' . uniqid() . '.' . $image->getClientOriginalExtension();

                if (!file_exists('uploads/History')) {
                    mkdir('uploads/History', 0777, true);
                }
                $image->move('uploads/History', $imagename);
            } else {
                $imagename = "default.png";
            }
            $history = History::create([
                'title' => $request->title,
                'year' => $request->year,
                'image' => $imagename,
                'description' => $request->description,
            ]);
            $history->save();

            $log = new Log();
            $log->action = "A New History Created";
            $log->user_id = Auth::user()->id;
            $log->save();
            return redirect()->route('admin.histories')->with('successMsg', 'History Successfully Saved!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function update(Request $request, $id)
    {
        try {
            $this->validate($request, [
                'title' => 'required',
                'year' => 'required',
                'description' => 'required',
            ]);
            $history = History::find($id);
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $slug = Str::slug($request->title);
                if (isset($image)) {
                    $currentDate = Carbon::now()->toDateString();
                    $imagename = $slug . '-' . $currentDate . '-' . uniqid() . '.' . $image->getClientOriginalExtension();

                    if (!file_exists('uploads/History')) {
                        mkdir('uploads/History', 0777, true);
                    }
                    $image->move('uploads/History', $imagename);
                    $history->image = $imagename;
                }
            }
            $history->title = $request->title;
            $history->year = $request->year;
            $history->description = $request->description;
            $history->save();
            $log = new Log();
            $log->action = "A history information updated";
            $log->user_id = Auth::user()->id;
            $log->save();
            return redirect()->route('admin.histories')->with('successMsg', 'History Updated!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function restore(int $id)
    {
        try {
            $history = History::onlyTrashed()->findOrFail($id);
            $history->restore();
            $log = new Log();
            $log->action = "A History restored";
            $log->user_id = Auth::user()->id;
            $log->save();
            return redirect()->back()->with('successMsg', 'History Restored Succesfully!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function onlyTrashed()
    {
        try {
            $histories = History::onlyTrashed()->get();
            return view('admin.history.trashed', compact('histories'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function permanent(Request $request, $id)
    {
        try {
            History::onlyTrashed()->find($id)->forceDelete();
            $log = new Log();
            $log->action = "A History deleted";
            $log->user_id = Auth::user()->id;
            $log->save();
            return redirect()->back()->with('successMsg', 'Permanently Deleted Succesfully!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function delete(Request $request, $id)
    {
        try {
            $history = History::find($id);
            $history->delete();
            $log = new Log();
            $log->action = "A History deleted";
            $log->user_id = Auth::user()->id;
            $log->save();
            return redirect()->back()->with('successMsg', 'Deleted Succesfully!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
}
