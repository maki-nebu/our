<?php

namespace App\Http\Controllers;

use App\Models\TenderCategory;
use App\Models\Log;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TenderCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:tendercategory_access', ['only' => ['index', 'show']]);
        $this->middleware('permission:tendercategory_create', ['only' => ['create', 'store']]);
        $this->middleware('permission:tendercategory_edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:tendercategory_delete', ['only' => ['destroy']]);
    }
    public function index()
    {
        try {
            $tendercategory = TenderCategory::orderByDesc('updated_at')->get();
            return view('admin.tendercategory.index', compact('tendercategory'));
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
            return view('admin.tendercategory.create');
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
                'name' => 'required',

            ]);
            $tendercat = TenderCategory::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name),
            ]);
            $tendercat->save();

            $log = new Log();
            $log->action = "A New Tender Category Created";
            $log->user_id = Auth::user()->id;
            $log->save();
            return redirect()->route('admin.tendercategories')->with('successMsg', 'Tender Category Successfully Saved!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(TenderCategory $tenderCategory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TenderCategory $tenderCategory)
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
    public function destroy(TenderCategory $tenderCategory)
    {
        //
    }
}
