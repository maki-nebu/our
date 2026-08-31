<?php

namespace App\Http\Controllers;

use App\Models\Hero;
use App\Models\CtaSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BannersController extends Controller
{

public function __construct()
{
    $this->middleware('permission:banners_access')->only('index');
    $this->middleware('permission:banners_create')->only(['create', 'store']);
    $this->middleware('permission:banners_edit')->only(['edit', 'update']);
    $this->middleware('permission:banners_delete')->only('destroy');
}

    // Index - show both hero slides and CTA sections
    public function index()
    {
        $heroes = Hero::orderBy('order')->get();
        $ctas = CtaSection::all();
        return view('admin.banners.index', compact('heroes', 'ctas'));
    }

    // Create - separate forms will handle both hero & CTA
    public function create()
    {
        return view('admin.banners.create');
    }

    // Store
    public function store(Request $request, $type)
    {
        if ($type === 'hero') {
            $request->validate([
                'image' => 'required|image|max:2048',
                'title_en' => 'nullable|string|max:255',
                'title_am' => 'nullable|string|max:255',
                'description_en' => 'nullable|string',
                'description_am' => 'nullable|string',
                'button_text_en' => 'nullable|string|max:255',
                'button_text_am' => 'nullable|string|max:255',
                'button_link' => 'nullable|string|max:255',
                'order' => 'nullable|integer',
                'status' => 'required|boolean',
            ]);

            $path = $request->file('image')->store('hero', 'public');

            Hero::create([
                'image' => $path,
                'title_en' => $request->title_en,
                'title_am' => $request->title_am,
                'description_en' => $request->description_en,
                'description_am' => $request->description_am,
                'button_text_en' => $request->button_text_en,
                'button_text_am' => $request->button_text_am,
                'button_link' => $request->button_link,
                'order' => $request->order ?? 0,
                'status' => $request->status,
            ]);

            return redirect()->route('admin.banners.index')->with('success', 'Hero slide created successfully!');
        }

        if ($type === 'cta') {
            $request->validate([
                'title_en' => 'required|string|max:255',
                'title_am' => 'required|string|max:255',
                'description_en' => 'required|string',
                'description_am' => 'required|string',
                'button_text_en' => 'nullable|string|max:255',
                'button_text_am' => 'nullable|string|max:255',
                'button_link' => 'nullable|string|max:255',
                'status' => 'required|boolean',
            ]);

            CtaSection::create($request->all());

            return redirect()->route('admin.banners.index')->with('success', 'CTA section created successfully!');
        }
    }

public function edit($type, $id)
{
    if ($type === 'hero') {
        $hero = Hero::findOrFail($id);
        return view('admin.banners.edit', compact('hero', 'type'));
    }

    if ($type === 'cta') {
        $cta = CtaSection::findOrFail($id);
        return view('admin.banners.edit', compact('cta', 'type'));
    }

    abort(404); // fallback for unknown types
}


    // Update
    public function update(Request $request, $type, $id)
    {
        if ($type === 'hero') {
            $hero = Hero::findOrFail($id);
            $request->validate([
                'image' => 'nullable|image|max:2048',
                'title_en' => 'nullable|string|max:255',
                'title_am' => 'nullable|string|max:255',
                'description_en' => 'nullable|string',
                'description_am' => 'nullable|string',
                'button_text_en' => 'nullable|string|max:255',
                'button_text_am' => 'nullable|string|max:255',
                'button_link' => 'nullable|string|max:255',
                'order' => 'nullable|integer',
                'status' => 'required|boolean',
            ]);

            if ($request->hasFile('image')) {
                Storage::disk('public')->delete($hero->image);
                $path = $request->file('image')->store('hero', 'public');
                $hero->image = $path;
            }

            $hero->update($request->except('image') + ['image' => $hero->image]);

            return redirect()->route('admin.banners.index')->with('success', 'Hero slide updated successfully!');
        }

        if ($type === 'cta') {
            $cta = CtaSection::findOrFail($id);
            $request->validate([
                'title_en' => 'required|string|max:255',
                'title_am' => 'required|string|max:255',
                'description_en' => 'required|string',
                'description_am' => 'required|string',
                'button_text_en' => 'nullable|string|max:255',
                'button_text_am' => 'nullable|string|max:255',
                'button_link' => 'nullable|string|max:255',
                'status' => 'required|boolean',
            ]);

            $cta->update($request->all());

            return redirect()->route('admin.banners.index')->with('success', 'CTA section updated successfully!');
        }
    }

    // Destroy
    public function destroy($type, $id)
    {
        if ($type === 'hero') {
            $hero = Hero::findOrFail($id);
            Storage::disk('public')->delete($hero->image);
            $hero->delete();
            return redirect()->route('admin.banners.index')->with('success', 'Hero slide deleted successfully!');
        }

        if ($type === 'cta') {
            $cta = CtaSection::findOrFail($id);
            $cta->delete();
            return redirect()->route('admin.banners.index')->with('success', 'CTA section deleted successfully!');
        }
    }
}
