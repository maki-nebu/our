<?php

namespace App\Http\Controllers;

use App\Models\Partnership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PartnershipController extends Controller
{
    /**
     * FRONTEND
     */
    public function index()
    {
        $partnerships = Partnership::all();
        return view('front.partners', compact('partnerships'));
    }

    /**
     * ADMIN: Show all partnerships
     */
    public function adminIndex()
    {
        $partnerships = Partnership::latest()->get();
        return view('admin.partnerships.index', compact('partnerships'));
    }

    /**
     * ADMIN: Show create form
     */
    public function create()
    {
        return view('admin.partnerships.create');
    }

    /**
     * ADMIN: Store partnership
     */
    public function store(Request $request)
    {
        $request->validate([
            'name_en' => 'required|string|max:255',
            'name_am' => 'required|string|max:255',
            'logo'    => 'required|image|max:2048',
        ]);

        $path = $request->file('logo')->store('partnerships', 'public');

        Partnership::create([
            'name_en' => $request->name_en,
            'name_am' => $request->name_am,
            'logo'    => $path,
        ]);

        return redirect()->route('admin.partnerships.index')->with('success', 'Partnership added successfully!');
    }

    /**
     * ADMIN: Edit form
     */
    public function edit($id)
    {
        $partnership = Partnership::findOrFail($id);
        return view('admin.partnerships.edit', compact('partnership'));
    }

    /**
     * ADMIN: Update partnership
     */
    public function update(Request $request, $id)
    {
        $partnership = Partnership::findOrFail($id);

        $request->validate([
            'name_en' => 'required|string|max:255',
            'name_am' => 'required|string|max:255',
            'logo'    => 'nullable|image|max:2048',
        ]);

        $data = [
            'name_en' => $request->name_en,
            'name_am' => $request->name_am,
        ];

        if ($request->hasFile('logo')) {
            // Delete old logo
            if ($partnership->logo && Storage::disk('public')->exists($partnership->logo)) {
                Storage::disk('public')->delete($partnership->logo);
            }
            $data['logo'] = $request->file('logo')->store('partnerships', 'public');
        }

        $partnership->update($data);

        return redirect()->route('admin.partnerships.index')->with('success', 'Partnership updated successfully!');
    }

    /**
     * ADMIN: Delete partnership
     */
    public function destroy($id)
    {
        $partnership = Partnership::findOrFail($id);

        if ($partnership->logo && Storage::disk('public')->exists($partnership->logo)) {
            Storage::disk('public')->delete($partnership->logo);
        }

        $partnership->delete();

        return redirect()->route('admin.partnerships.index')->with('success', 'Partnership deleted successfully!');
    }
}
