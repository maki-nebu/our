<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FaqController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:faq_access', ['only' => ['index', 'show']]);
        $this->middleware('permission:faq_create', ['only' => ['create', 'store']]);
        $this->middleware('permission:faq_edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:faq_delete', ['only' => ['destroy']]);
    }

    /** Display a listing of FAQs */
    public function index()
    {
        $faqs = Faq::orderByDesc('updated_at')->get();
        return view('admin.faq.index', compact('faqs'));
    }

    /** Show form to create new FAQ */
public function create()
{
    $categories = \App\Models\FaqCategory::all(); // fetch all FAQ categories
    return view('admin.faq.create', compact('categories'));
}

    /** Store a newly created FAQ */
public function store(Request $request)
{
    $request->validate([
        'faq_category_id' => 'required|exists:faq_categories,id',
        'question_en'     => 'required|string|max:255',
        'question_am'     => 'required|string|max:255',
        'answer_en'       => 'required|string',
        'answer_am'       => 'required|string',
        'status'          => 'required|boolean',
    ]);

    \App\Models\Faq::create([
        'faq_category_id' => $request->faq_category_id,
        'question_en'     => $request->question_en,
        'question_am'     => $request->question_am,
        'answer_en'       => $request->answer_en,
        'answer_am'       => $request->answer_am,
        'status'          => $request->status,
    ]);

    return redirect()->route('admin.faqs.index')
                     ->with('success', 'FAQ added successfully!');
}

    /** Show form to edit FAQ */
public function edit(Faq $faq)
{
    $categories = \App\Models\FaqCategory::all();
    return view('admin.faq.edit', compact('faq', 'categories'));
}


    /** Update an existing FAQ */
   public function update(Request $request, Faq $faq)
{
    $request->validate([
        'faq_category_id' => 'required|exists:faq_categories,id',
        'question_en'     => 'required|string|max:255',
        'question_am'     => 'required|string|max:255',
        'answer_en'       => 'required|string',
        'answer_am'       => 'required|string',
        'status'          => 'required|boolean',
    ]);

    $faq->update([
        'faq_category_id' => $request->faq_category_id,
        'question_en'     => $request->question_en,
        'question_am'     => $request->question_am,
        'answer_en'       => $request->answer_en,
        'answer_am'       => $request->answer_am,
        'status'          => $request->status,
    ]);

    return redirect()->route('admin.faqs.index')
                     ->with('success', 'FAQ updated successfully!');
}


    /** Soft delete FAQ */
    public function destroy(Faq $faq)
    {
        $faq->delete();

        Log::create([
            'action'  => "A Faq deleted",
            'user_id' => Auth::id(),
        ]);

        return redirect()->back()->with('successMsg', 'Deleted Successfully!');
    }

    /** Show only trashed FAQs */
    public function onlyTrashed()
    {
        $faqs = Faq::onlyTrashed()->get();
        return view('admin.faq.trashed', compact('faqs'));
    }

    /** Restore trashed FAQ */
    public function restore($id)
    {
        $faq = Faq::onlyTrashed()->findOrFail($id);
        $faq->restore();

        return redirect()->back()->with('successMsg', 'Faq Restored!');
    }

    /** Permanently delete FAQ */
    public function permanent($id)
    {
        Faq::onlyTrashed()->findOrFail($id)->forceDelete();

        Log::create([
            'action'  => "A Faq permanently deleted",
            'user_id' => Auth::id(),
        ]);

        return redirect()->back()->with('successMsg', 'Permanently Deleted Successfully!');
    }
}
