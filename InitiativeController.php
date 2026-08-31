<?php

namespace App\Http\Controllers;

use Intervention\Image\Facades\Image;
use App\Models\Initiative;
use App\Models\Log;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class InitiativeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:initiative_access', ['only' => ['index', 'show']]);
        $this->middleware('permission:initiative_create', ['only' => ['create', 'store']]);
        $this->middleware('permission:initiative_edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:initiative_delete', ['only' => ['destroy']]);
    }
    public function index()
    {
        try {
            $initiative = Initiative::latest()->get();
            return view('admin.initiative.index', compact('initiative'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function create()
    {
        try {
            $initiatives = Initiative::where('status', 1)->latest()->get();
            return view('admin.initiative.create', compact('initiatives'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            // Validate the form data
            $validatedData = $request->validate([
                'initiative_name' => 'required|string',
                'initiative_name_am' => 'required|string',
                'initiative_photo.*' => 'required|image|mimes:jpeg,png,jpg,gif',
                'initiative_file' => 'nullable|mimes:pdf,doc,docx',
                'description' => 'required|string',
                'description_am' => 'required|string',
            ]);

            $initiative = Initiative::create([
                'initiative_name' => $request->initiative_name,
                'initiative_name_am' => $request->initiative_name_am,
                'slug' => Str::slug($request->initiative_name),
                'description' => $request->description,
                'description_am' => $request->description_am,
            ]);

            $initiative->save();
            if ($request->hasFile('initiative_photo')) {
                $initiativePhotos = [];

                foreach ($request->file('initiative_photo') as $photo) {
                    $photoName = $photo->getClientOriginalName();
                    $photo->move(public_path('uploads/InitiativePhoto'), $photoName);
                    $initiativePhotos[] = $photoName;
                }

                $initiative->initiative_photo = json_encode($initiativePhotos);
                $initiative->save();
            }

            if ($request->hasFile('initiative_file')) {
                $initiativeFile = $request->file('initiative_file');
                $fileName = $initiativeFile->getClientOriginalName();
                $initiativeFile->move(public_path('uploads/InitiativeFile'), $fileName);

                $initiative->initiative_file = $fileName;
                $initiative->save();
            }

            $log = new Log();
            $log->action = 'Initiative Created';
            $log->user_id = Auth::user()->id;
            $log->save();

            return redirect()->route('admin.initiatives')->with('successMsg', 'Initiatives Successfully Saved');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $initiativess = Initiative::find($id);
            return view('admin.initiative.edit', compact('initiativess'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            // Validate the form data
            $validatedData = $request->validate([
                'initiative_name' => 'required|string',
                'initiative_name_am' => 'required|string',
                'initiative_photo.*' => 'nullable|image|mimes:jpeg,png,jpg,gif',
                'initiative_file' => 'nullable|mimes:pdf,doc,docx',
                'description' => 'required|string',
                'description_am' => 'required|string',
            ]);

            $initiative = Initiative::findOrFail($id);

            $initiative->initiative_name = $request->initiative_name;
            $initiative->initiative_name_am = $request->initiative_name_am;
            $initiative->slug = Str::slug($request->initiative_name);
            $initiative->description = $request->description;
            $initiative->description_am = $request->description_am;

            if ($request->hasFile('initiative_photo')) {
                $initiativePhotos = [];

                foreach ($request->file('initiative_photo') as $photo) {
                    $photoName = $photo->getClientOriginalName();
                    $photo->move(public_path('uploads/InitiativePhoto'), $photoName);
                    $initiativePhotos[] = $photoName;
                }

                $initiative->initiative_photo = json_encode($initiativePhotos);
            }

            if ($request->hasFile('initiative_file')) {
                $initiativeFile = $request->file('initiative_file');
                $fileName = $initiativeFile->getClientOriginalName();
                $initiativeFile->move(public_path('uploads/InitiativeFile'), $fileName);

                $initiative->initiative_file = $fileName;
            }

            $initiative->save();

            $log = new Log();
            $log->action = 'Initiative Updated';
            $log->user_id = Auth::user()->id;
            $log->save();

            return redirect()->route('admin.initiatives')->with('successMsg', 'Initiative Successfully Updated');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }


    public function delete($id)
    {
        try {
            $initiativ = Initiative::find($id);
            $initiativ->delete();
            $log = new Log();
            $log->action = "A Initiative deleted";
            $log->user_id = Auth::user()->id;
            $log->save();
            return redirect()->back()->with('successMsg', 'Initiative Successfully Deleted!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
}
