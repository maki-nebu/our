<?php
namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\Application;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;


class ApplicationController extends Controller
{
    public function index()
    {
        $applications = Application::all();
        return view('admin.application.index', compact('applications'));
    }
    public function create()
    {
        return view('admin.application.create');
    }
    public function edit($id)
    {
        $application = Application::find($id);
        return view('admin.application.edit', compact('application'));
    }
    public function show($id)
    {
        $application = Application::find($id);
        return view('admin.application.edit', compact('application'));
    }
    public function store(Request $request)
    {
        $this->validate($request, [
            'title' => 'required',
            'description' => 'required',
            'image' => 'required|mimes:jpeg,jpg,bmp,png',
        ]);
        $image = $request->file('image');
        $slug = Str::slug($request->name);
        if (isset($image)) {
            $currentDate = Carbon::now()->toDateString();
            $imagename = $slug . '-' . $currentDate . '-' . uniqid() . '.' . $image->getClientOriginalExtension();

            if (!file_exists('uploads/Application')) {
                mkdir('uploads/Application', 0777, true);
            }
            $image->move('uploads/Application', $imagename);
        } else {
            $imagename = "default.png";
        }
        $application = Application::create([
            'title' => $request->title,
            'description' => $request->description,
            'icon' => $request->icon,
            'image' => $imagename,
        ]);
        $application->save();

        $log = new Log();
        $log->action = "A New Application Created";
        $log->user_id = Auth::user()->id;
        $log->save();
        return redirect()->route('admin.applications')->with('successMsg', 'Application Successfully Saved!');
    }
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'title' => 'required',
            'description' => 'required',
            'icon' => 'required',
        ]);
        $application = Application::find($id);
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $slug = Str::slug($request->name);
            if (isset($image)) {
                $currentDate = Carbon::now()->toDateString();
                $imagename = $slug . '-' . $currentDate . '-' . uniqid() . '.' . $image->getClientOriginalExtension();

                if (!file_exists('uploads/Application')) {
                    mkdir('uploads/Application', 0777, true);
                }
                $image->move('uploads/Application', $imagename);
                $application->image = $imagename;
            }
        }
        $application->title = $request->title;
        $application->description = $request->description;

        $application->status = $request->status;

        $application->save();
        $log = new Log();
        $log->action = "A application information updated";
        $log->user_id = Auth::user()->id;
        $log->save();
        return redirect()->route('admin.applications')->with('successMsg', 'Application Updated!');
    }
    public function restore(int $id)
    {
        $application = Application::onlyTrashed()->findOrFail($id);
        $application->restore();
        return redirect()->back()->with('successMsg', 'Application Restored!');
    }
    public function onlyTrashed()
    {
        $applications = Application::onlyTrashed()->get();
        return view('admin.application.trashed', compact('applications'));
    }
    public function permanent(Request $request, $id)
    {
        Application::onlyTrashed()->find($id)->forceDelete();
        $log = new Log();
        $log->action = " An Application deleted";
        $log->user_id = Auth::user()->id;
        $log->save();
        return redirect()->back()->with('successMsg', 'Permanently Deleted Succesfully!');
    }
    public function delete(Request $request, $id)
    {
        $event = Application::find($id);
        $event->delete();
        $log = new Log();
        $log->action = "A Application deleted";
        $log->user_id = Auth::user()->id;
        $log->save();
        return redirect()->back()->with('successMsg', 'Deleted Succesfully!');
    }
}
