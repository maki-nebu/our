<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Doctor;
use App\Models\Gallery;
use App\Models\GalleryCategory;
use App\Models\Specialty;
use App\Models\Directorate;
use App\Models\Service;
use App\Models\News;
use App\Models\Department;
use App\Models\Faq;
use App\Models\Accreditation;
use App\Models\Feature;
use App\Models\About;
use App\Models\Setting;
use App\Models\Testimony;
use App\Models\Partnership;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;



class HomeController extends Controller
{

public function __construct()
{
    // Fetch the first (or only) settings row
    $setting = Setting::first();

    // Share site settings
    view()->share('setting', $setting);

    // Fetch visibility settings (assuming only one row)
    $visibility = \App\Models\Visibility::first();
    view()->share('visibility', $visibility);
}


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
public function index()
{
    
    $partnerships   = Partnership::all();

    $faqs = Faq::orderBy('created_at', 'desc')->get();

    
    // Limit doctors for homepage display to reduce load
    $doctors = Doctor::with('specialty')
        ->orderBy('created_at', 'desc')
        ->take(6)
        ->get();

    // Limit specialties (safe)
    $specialties = Specialty::take(10)->get();

    // Fetch latest galleries
    $galleries = Gallery::latest()->take(10)->get();

    // Fetch categories for gallery dropdown
    $categories = GalleryCategory::all();

    // Fetch all departments (or limit if you want)
    $departments = Department::all();
    $departmentCount = Department::where('is_active', 1)->count();

    // ✅ Fetch features for the featured services section 
    $features = Feature::where('is_active', true)
                       ->orderBy('order')
                       ->get();

    // Fetch 4 active services for features section
    $services = Service::where('status', 1)
        ->orderBy('created_at', 'desc')
        ->get()
        ->unique('directorate_id') 
        ->take(4); 

    $abouts = About::where('status', 1)->get();

    // Separate entries by type
    $mission = $abouts->where('type', 'mission')->first();
    $vision = $abouts->where('type', 'vision')->first();
    $coreValues = $abouts->firstWhere('type', 'core');
    $description = $abouts->where('type', 'description')->first();
    $hospitalHistory = $abouts->where('type', 'hospital history')->first();
    $milestones = $abouts->where('type', 'milestone');

    // Stats for the stats section
    $doctorCount = Doctor::count();
    $directorateCount = Directorate::count();
    $serviceCount = Service::count();
    $departmentCount = Department::where('is_active', 1)->count();
    $accreditationCount = Accreditation::where('is_active', 1)->count();

   $testimonies = Testimony::where('is_active', 1)
    ->orderBy('created_at', 'desc')
    ->get();

        $latestNews = News::where('is_published', true)
        ->latest('published_at')
        ->take(3)
        ->get();

          $accreditations = Accreditation::where('is_active', 1)->orderBy('order')->get();


    return view('front.home', compact(
        'doctors', 
        'specialties', 
        'galleries',
        'categories', 
        'departments',
        'doctorCount', 
        'directorateCount', 
        'serviceCount',
        'departmentCount',
        'features',
        'services',
        'faqs',
        'mission', 
        'vision', 
        'coreValues',
        'description', 
        'hospitalHistory',
        'milestones',
        'accreditationCount',
        'testimonies',
         'partnerships',
         'latestNews',
         'accreditations'
    ));
}

    public function faq()
    {
        // Fetch all FAQs from the table
        $faqs = Faq::all();

        // Pass it to the view
        return view('front.faq', compact('faqs'));
    }
 public function testimonial()
{
    return view('front.testimonials'); 
}
public function news()
    {
         $news = News::orderBy('created_at', 'desc')->paginate(10); // 10 per page

        // Categories with count
        $categories = News::select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->get();

        // Popular news (top 5 by views)
        $popularNews = News::orderBy('views', 'desc')->take(5)->get();

        // Pass all variables to the view
        return view('front.news', compact('news', 'categories', 'popularNews'));
    }

    private function calculateReadTime($content)
{
    // Strip HTML and count words
    $wordCount = str_word_count(strip_tags($content));
    $wordsPerMinute = 200; // average reading speed

    return ceil($wordCount / $wordsPerMinute);
}


public function showNews($slug)
{
    $newsItem = News::where('slug', $slug)
        ->where('is_published', true)
        ->firstOrFail();

    // Calculate read time
    $readTime = $this->calculateReadTime($newsItem->content_en);

    // Increment views
    $newsItem->views++;
    $newsItem->save();

    // Related news (optional, same as in your NewsController)
    $relatedNews = News::where('category', $newsItem->category)
        ->where('id', '!=', $newsItem->id)
        ->where('is_published', true)
        ->orderBy('published_at', 'desc')
        ->take(3)
        ->get();

    return view('front.news-detail', compact('newsItem', 'readTime', 'relatedNews'));
}
}
