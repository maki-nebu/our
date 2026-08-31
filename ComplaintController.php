<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    // Front-end: show complaint form
    public function create()
    {
        return view('front.complaints');
    }

    // Front-end: store complaint
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'department' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'description' => 'required|string',
            'file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120', // max 5MB
        ]);

        $data = $request->only([
            'name','email','phone','department','location','description','address'
        ]);

        if ($request->hasFile('file')) {
            $data['file'] = $request->file('file')->store('complaint_files', 'public');
        }

        $data['language'] = app()->getLocale();

        Complaint::create($data);

        return redirect()->back()->with('success', __('complaint.submitted'));
    }

    // Admin: list all complaints
    public function index()
    {
        $complaints = Complaint::latest()->get(); // newest first
        return view('admin.complaints.index', compact('complaints'));
    }

    // Admin: show single complaint
    public function show($id)
    {
        $complaint = Complaint::findOrFail($id);
        return view('admin.complaints.show', compact('complaint'));
    }

    // Admin: delete complaint
    public function destroy($id)
    {
        $complaint = Complaint::findOrFail($id);
        if ($complaint->file) {
            \Storage::disk('public')->delete($complaint->file);
        }
        $complaint->delete();
        return redirect()->back()->with('success', 'Complaint deleted successfully.');
    }
}
