<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Directorate;
use App\Models\Log;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DirectorateController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:directorate_access', ['only' => ['index']]);
        $this->middleware('permission:directorate_create', ['only' => ['create', 'store']]);
        $this->middleware('permission:directorate_edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:directorate_delete', ['only' => ['destroy']]);
    }

    public function index()
    {
        $directorates = Directorate::latest()->get();
        return view('admin.directorate.index', compact('directorates'));
    }

    public function create()
    {
        $departments = Department::where('status', 1)->latest()->get();
        return view('admin.directorate.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'department_id' => 'required',
            'description' => 'required',
            'message' => 'required',
            'director_name' => 'required',
            'director_photo' => 'required|mimes:jpeg,jpg,bmp,png,svg',
        ]);
        $image1 = $request->file('director_photo');
        $slug = Str::slug($request->director_name);
        if (isset($image1)) {
            $currentDate = Carbon::now()->toDateString();
            $imagename1 = $slug . '-' . $currentDate . '-' . uniqid() . '.' . $image1->getClientOriginalExtension();

            if (!file_exists('uploads/Directorate')) {
                mkdir('uploads/Directorate', 0777, true);
            }
            $image1->move('uploads/Directorate', $imagename1);
        } else {
            $imagename1 = "default.png";
        }

        $directorate = Directorate::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'department_id' => $request->department_id,
            'description' => $request->description,
            'message' => $request->message,
            'director_name' => $request->director_name,
            'director_photo' => $imagename1
        ]);
        $directorate->save();

        $log = new Log();
        $log->action = "A New Directorate Created";
        $log->user_id = Auth::user()->id;
        $log->save();

        return redirect()->route('admin.directorates')->with('successMsg', 'Directorate Successfully Saved');
    }


    public function edit($id)
    {
        $directorate = Directorate::find($id);
        $departments = Department::where('status', 1)->latest()->get();
        return view('admin.directorate.edit', compact('directorate', 'departments'));
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required',
            'department_id' => 'required',
            'description' => 'required',
            'message' => 'required',
            'director_name' => 'required',
            'director_photo' => 'nullable|mimes:jpeg,jpg,bmp,png,svg',
        ]);

        $directorate = Directorate::find($id);
        $slug = Str::slug($request->name);

        if ($request->hasFile('director_photo')) {
            $image1 = $request->file('director_photo');
            $currentDate = Carbon::now()->toDateString();
            $imagename1 = $slug . '-' . $currentDate . '-' . uniqid() . '.' . $image1->getClientOriginalExtension();

            if (!file_exists('uploads/Directorate')) {
                mkdir('uploads/Directorate', 0777, true);
            }
            $image1->move('uploads/Directorate', $imagename1);
            $directorate->director_photo = $imagename1;
        } else {
            $imagename1 = "default.png";
        }

        $directorate->name = $request->name;
        $directorate->department_id = $request->department_id;
        $directorate->description = $request->description;
        $directorate->message = $request->message;
        $directorate->director_name = $request->director_name;
        $directorate->slug = Str::slug($request->name);
        $directorate->save();

        $log = new Log();
        $log->action = "A Directorate updated";
        $log->user_id = Auth::user()->id;
        $log->save();

        return redirect()->route('admin.directorates')->with('successMsg', 'Directorate Successfully Updated');
    }

    public function status($id)
    {
        try {
            $item = Directorate::findOrFail($id);
            $item->status = !$item->status;
            $item->save();
            $log = new Log();
            $log->action = "A Directorate activated / deactivated";
            $log->user_id = Auth::user()->id;
            $log->save();
            return redirect()->back()->with('successMsg', 'Directorate Activated / Deactivated!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function destroy(Directorate $directorate)
    {
        //
    }
}
