<?php

namespace App\Http\Controllers;

use App\Models\Tender;
use App\Models\Log;
use App\Models\TenderCategory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use RealRashid\SweetAlert\Facades\Alert;

class TenderController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:tender_access', ['only' => ['index', 'show']]);
        $this->middleware('permission:tender_create', ['only' => ['create', 'store']]);
        $this->middleware('permission:tender_edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:tender_delete', ['only' => ['destroy']]);
    }
    public function index()
    {
        try {
            $tenders = Tender::all();
            return view('admin.tender.index', compact('tenders'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        try {
            $tendercategories = TenderCategory::latest()->get();
            return view('admin.tender.create', compact('tendercategories'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $this->validate($request, [
                'title' => 'required',
                'tender_category_id' => 'required',
                'description' => 'required',
                'file' => 'required|mimes:pdf,doc,docx|max:100000',
            ]);

            $file = $request->file('file');
            $slug = Str::slug($request->title);
            
            if (isset($file)) {
                $currentDate = Carbon::now()->toDateString();
                $filename = $slug . '-' . $currentDate . '-' . uniqid() . '.' . $file->getClientOriginalExtension();

                if (!file_exists('uploads/Tender')) {
                    mkdir('uploads/Tender', 0777, true);
                }
                $file->move('uploads/Tender', $filename);
            } else {
                $filename = "default.png";
            }

            $tender = Tender::create([
                'title' => $request->title,
                'slug' => Str::slug($request->title),
                'tender_category_id' => $request->tender_category_id,
                'description' => $request->description,
                'file' => $filename,
            ]);
            $tender->save();

            $log = new Log();
            $log->action = "A New Tender Created";
            $log->user_id = Auth::user()->id;
            $log->save();
            toast('Tender Successfully Saved!', 'success');
            return redirect()->route('admin.tenders');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Tender $tender)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Tender $tender)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tender $tender)
    {
        //
    }
}
