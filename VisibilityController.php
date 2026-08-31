<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Visibility;

class VisibilityController extends Controller
{
    public function edit()
    {
        $visibility = HomeVisibility::first(); // assume only one row
        return view('admin.home_visibility.edit', compact('visibility'));
    }

    public function update(Request $request)
    {
        $visibility = Visibility::first();
        $data = $request->all();

        // Convert checkbox values to boolean
        foreach ($data as $key => $value) {
            $data[$key] = $value == 'on' ? true : false;
        }

        $visibility->update($data);

        return redirect()->back()->with('success', 'Homepage settings updated successfully.');
    }
}
