<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\Publication;
use App\Models\PublicationCategory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use RealRashid\SweetAlert\Facades\Alert;

class PublicationController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:publication_access', ['only' => ['index', 'show']]);
        $this->middleware('permission:publication_create', ['only' => ['create', 'store']]);
        $this->middleware('permission:publication_edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:publication_delete', ['only' => ['destroy']]);
    }

    public function index()
    {
        try {
            $publications = Publication::all();
            return view('admin.publication.index', compact('publications'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function create()
    {
        try {
            $publicationcategories = PublicationCategory::where('status', 1)->latest()->get();
            return view('admin.publication.create', compact('publicationcategories'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function edit($id)
    {
        try {
            $publication = Publication::find($id);
            $publicationcategories = PublicationCategory::where('status', 1)->latest()->get();
            return view('admin.publication.edit', compact('publication', 'publicationcategories'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function store(Request $request)
    {
        try {
            $this->validate($request, [
                'title' => 'required',
                'publication_category_id' => 'required',
                'description' => 'required',
                'file' => 'required|mimes:pdf,doc,docx|max:100000',
                'cover_image' => 'required|mimes:jpeg,jpg,bmp,png,svg',
            ]);

            $cover_image = $request->file('cover_image');
            $file = $request->file('file');
            $slug = Str::slug($request->title);
            if (isset($cover_image)) {
                $currentDate = Carbon::now()->toDateString();
                $imagename = $slug . '-' . $currentDate . '-' . uniqid() . '.' . $cover_image->getClientOriginalExtension();

                if (!file_exists('uploads/Publication')) {
                    mkdir('uploads/Publication', 0777, true);
                }
                $cover_image->move('uploads/Publication', $imagename);
            } else {
                $imagename = "default.png";
            }

            if (isset($file)) {
                $currentDate = Carbon::now()->toDateString();
                $filename = $slug . '-' . $currentDate . '-' . uniqid() . '.' . $file->getClientOriginalExtension();

                if (!file_exists('uploads/Publication')) {
                    mkdir('uploads/Publication', 0777, true);
                }
                $file->move('uploads/Publication', $filename);
            } else {
                $filename = "default.png";
            }

            $publication = Publication::create([
                'title' => $request->title,
                'slug' => Str::slug($request->title),
                'publication_category_id' => $request->publication_category_id,
                'description' => $request->description,
                'file' => $filename,
                'cover_image' => $imagename,
            ]);
            $publication->save();

            $log = new Log();
            $log->action = "A New Publication Created";
            $log->user_id = Auth::user()->id;
            $log->save();
            toast('Publication Successfully Saved!', 'success');
            return redirect()->route('admin.publications');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function update(Request $request, $id)
    {
        try {
            $this->validate($request, [
                'title' => 'required',
                'publication_category_id' => 'required',
                'description' => 'required',
                'file' => 'nullable|mimes:pdf,doc,docx|max:5048',
                'cover_image' => 'nullable|mimes:jpeg,jpg,bmp,png,svg',
            ]);
            $publication = Publication::find($id);


            $cover_image = $request->file('cover_image');
            $file = $request->file('file');
            $slug = Str::slug($request->title);
            if ($request->hasFile('cover_image')) {
                if (isset($cover_image)) {
                    $currentDate = Carbon::now()->toDateString();
                    $imagename = $slug . '-' . $currentDate . '-' . uniqid() . '.' . $cover_image->getClientOriginalExtension();

                    if (!file_exists('uploads/Publication')) {
                        mkdir('uploads/Publication', 0777, true);
                    }
                    $cover_image->move('uploads/Publication', $imagename);
                    $publication->cover_image = $cover_image;
                } else {
                    $imagename = "default.png";
                }
            }
            if ($request->hasFile('file')) {
                if (isset($file)) {
                    $currentDate = Carbon::now()->toDateString();
                    $filename = $slug . '-' . $currentDate . '-' . uniqid() . '.' . $file->getClientOriginalExtension();

                    if (!file_exists('uploads/Publication')) {
                        mkdir('uploads/Publication', 0777, true);
                    }
                    $file->move('uploads/Publication', $filename);
                    $publication->file = $filename;
                } else {
                    $filename = "default.png";
                }
            }
            $publication->title = $request->title;
            $publication->publication_category_id = $request->publication_category_id;
            $publication->description = $request->description;
            $publication->save();

            $log = new Log();
            $log->action = "A publication information updated";
            $log->user_id = Auth::user()->id;
            $log->save();
            return redirect()->route('admin.publications')->with('successMsg', 'Publication Updated!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function restore(int $id)
    {
        try {
            $publication = Publication::onlyTrashed()->findOrFail($id);
            $publication->restore();
            toast('Publication Restored!', 'success');
            return redirect()->back();
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function onlyTrashed()
    {
        try {
            $publications = Publication::onlyTrashed()->get();
            return view('admin.publication.trashed', compact('publications'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function permanent(Request $request, $id)
    {
        try {
            Publication::onlyTrashed()->find($id)->forceDelete();
            $log = new Log();
            $log->action = " An Publication deleted";
            $log->user_id = Auth::user()->id;
            $log->save();
            toast('Permanently Deleted Succesfully!', 'success');
            return redirect()->back();
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function delete(Request $request, $id)
    {
        try {
            $publication = Publication::find($id);
            $publication->delete();
            $log = new Log();
            $log->action = " An Publication deleted";
            $log->user_id = Auth::user()->id;
            $log->save();
            toast('Permanently Deleted Succesfully!', 'success');
            return redirect()->back();
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
}
