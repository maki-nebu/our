<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HealthInfoCategory;

class HealthInfoCategoryController extends Controller
{
    // Show all categories
    public function index()
    {
        $categories = HealthInfoCategory::all();
        return view('admin.health_info_category.index', compact('categories'));
    }

    // Show create form
    public function create()
    {
        return view('admin.health_info_category.create');
    }

    // Store new category
    public function store(Request $request)
    {
       HealthInfoCategory::create([
    'name_en' => $request->name_en,
    'name_am' => $request->name_am,

]);

        return redirect()->route('admin.health_info_category.index')->with('success', 'Category added successfully.');
    }

    // Show edit form
    public function edit($id)
    {
        $category = HealthInfoCategory::findOrFail($id);
        return view('admin.health_info_category.edit', compact('category'));
    }

    // Update category
    public function update(Request $request, $id)
    {
        $category = HealthInfoCategory::findOrFail($id);

        $request->validate([
            'name_en' => 'required|string|max:255',
            'name_am' => 'required|string|max:255',
            
        ]);

        $category->update([
            'name_en' => $request->name_en,
            'name_am' => $request->name_am,
            'status' => $request->status,
        ]);

        return redirect()->route('admin.health_info_category.index')->with('success', 'Category updated successfully.');
    }

    // Delete category
    public function destroy($id)
    {
        $category = HealthInfoCategory::findOrFail($id);
        $category->delete();

        return redirect()->route('admin.health_info_category.index')->with('success', 'Category deleted successfully.');
    }
}
