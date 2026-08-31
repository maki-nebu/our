<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\About;
use App\Models\Leadership;
use App\Models\Accreditation;

use App\Models\HealthInfo; 

class PageController extends Controller
{
    public function about()
    {
        $locale = app()->getLocale();

        // About sections
        $aboutEntries = About::where('status', 1)->get();
        $history = About::where('type', 'history')->where('status', 1)->first();
        $milestones = About::where('type', 'milestone')->where('status', 1)->get();
        $others = About::whereIn('type', ['mission', 'vision', 'core'])->where('status', 1)->get();
        
        // Leadership sections
        $managementTeam = Leadership::active()->byCategory('management')->ordered()->get();
        $medicalLeadership = Leadership::active()->byCategory('medical')->ordered()->get();
        $boardMembers = Leadership::active()->byCategory('board')->ordered()->get();

        // Accreditations
        $accreditations = Accreditation::active()->ordered()->get();

        return view('front.about', compact(
            'aboutEntries',
            'history',
            'milestones',
            'others',
            'locale',
            'managementTeam',
            'medicalLeadership',
            'boardMembers',
            'accreditations'
        ));
    }



public function history()
{
    $locale = app()->getLocale();
    return view('front.history');
}




    public function contact()
    {
        return view('user.contact'); // If you have a contact page
    }

public function healthInfo()
{
    // Get only active files from the HealthInfo table, newest first
    $files = HealthInfo::where('is_active', true)
              ->orderBy('created_at', 'desc')
              ->get();

    return view('front.health_info', compact('files'));
}
    
    public function download($id)
{
    $file = HealthInfo::findOrFail($id);
    
    // Increment download count
    $file->download_count++;
    $file->save();
    
    // Get the full path to the file
    $pathToFile = storage_path('app/public/' . $file->file_path);
    
    // Check if file exists
    if (!file_exists($pathToFile)) {
        abort(404, 'File not found');
    }
    
    return response()->download($pathToFile);
}
public function accreditation()
{
    // Fetch accreditations from database
    $accreditations = \App\Models\Accreditation::all();

    // Return the view with the data
    return view('front.accreditation', compact('accreditations'));
}


}
