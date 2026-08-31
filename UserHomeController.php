<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Banner;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Comment;
use App\Models\Contact;
use App\Models\Department;
use App\Models\Directorate;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Faq;
use App\Models\Gallery;
use App\Models\HeadMessage;
use App\Models\History;
use App\Models\Partner;
use App\Models\Publication;
use App\Models\PublicationCategory;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Tender;
use App\Models\TenderCategory;
use App\Models\Testimony;
use App\Models\Vacancy;
use App\Models\Initiative;
use App\Models\Visibility;
use App\Rules\PhoneNumberRule;
use App\Rules\ReCaptcha;
use Carbon\Carbon;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class UserHomeController extends Controller
{
    public function index()
    {
        try {
            $banners = Banner::where('status', 1)->latest()->get();
            $departments = Department::where('status', 1)->latest()->get();
            $services = Service::where('status', 1)->latest()->take(14)->get();
            $directorates = Directorate::where('status', 1)->latest()->get();
            $events = Event::where('status', 1)->latest()->take(5)->get();
            $galleries = Gallery::where('status', 1)->latest()->take(5)->get();
            $publications = Publication::where('status', 1)->latest()->take(5)->get();
            $blogs = Blog::where('status', 1)->latest()->take(3)->get();
            $setting = Setting::find(1);
            $testimonies = Testimony::latest()->get();
            $visible = Visibility::find(1);
            $head_message = HeadMessage::find(1);
            $featured_news = Blog::latest()->take(3)->get();

            // homepage is expected in resources/views/front/home.blade.php
            return view('front.home', compact(
                'testimonies',
                'featured_news',
                'banners',
                'setting',
                'events',
                'galleries',
                'blogs',
                'departments',
                'head_message',
                'publications',
                'directorates',
                'services',
                'visible'
            ));
        } catch (\Throwable $th) {
            $setting = Setting::find(1);
            // show a friendly error page inside front/ if something goes wrong
            return view('front.wrong', compact('setting'));
        }
    }

    public function events()
    {
        try {
            $banners = Banner::all();
            $events = Event::latest()->take(500)->get();
            $categories = EventCategory::where('status', 1)->latest()->take(5)->get();
            $setting = Setting::find(1);
            return view('front.events', compact('banners', 'setting', 'events', 'categories'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function eventDetail($id)
    {
        try {
            $event = Event::find($id);
            $categories = EventCategory::where('status', 1)->latest()->take(5)->get();
            $related = Event::where('event_category_id', $event->event_category_id)->get();
            $setting = Setting::find(1);
            return view('front.eventsdetail', compact('setting', 'event', 'categories', 'related'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function initiatives()
    {
        try {
            $initiativs = Initiative::where('status', 1)->latest()->take(500)->get();
            $setting = Setting::find(1);
            return view('front.initiatives', compact('setting', 'initiativs'));
        } catch (\Throwable $th) {
            // toast helper used earlier; keep behavior
            toast($th, 'error');
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function initiativeDetail($slug)
    {
        try {
            $initiativesdetail = Initiative::where('slug', $slug)->first();
            $setting = Setting::find(1);
            return view('front.initiativedetails', compact('setting', 'initiativesdetail'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function blogs()
    {
        try {
            $blogs = Blog::latest()->take(5)->get();
            $categories = BlogCategory::where('status', 1)->latest()->take(5)->get();
            $setting = Setting::find(1);
            return view('front.blogs', compact('setting', 'blogs', 'categories'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function tenderDetail($id)
    {
        try {
            $tender = Tender::find($id);
            $tendercats = TenderCategory::where('status', 1)->latest()->get();
            $setting = Setting::find(1);
            return view('front.tenderdetail', compact('setting', 'tender', 'tendercats'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function publicationDetail($id)
    {
        try {
            $publication = Publication::find($id);
            $categories = PublicationCategory::where('status', 1)->latest()->get();
            $setting = Setting::find(1);
            return view('front.pubdetail', compact('setting', 'publication', 'categories'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function tenderCategory($name)
    {
        try {
            $category = TenderCategory::find($name);
            $tenders = Tender::where('tender_category_id', $category->id)->latest()->get();
            $tendercats = TenderCategory::where('status', 1)->latest()->get();
            $setting = Setting::find(1);
            return view('front.tender', compact('setting', 'publications', 'tendercats', 'category'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function publicationCategory($name)
    {
        try {
            $category = PublicationCategory::find($name);
            $publications = Publication::where('publication_category_id', $category->id)->latest()->get();
            $pubcats = PublicationCategory::where('status', 1)->latest()->get();
            $setting = Setting::find(1);
            return view('front.pubs', compact('setting', 'publications', 'pubcats'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function tender()
    {
        try {
            $tenders = Tender::latest()->take(500)->get();
            $tendercats = TenderCategory::where('status', 1)->latest()->get();
            $setting = Setting::find(1);
            return view('front.tender', compact('setting', 'tenders', 'tendercats'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function publications()
    {
        try {
            $publications = Publication::latest()->take(500)->get();
            $pubcats = PublicationCategory::where('status', 1)->latest()->get();
            $setting = Setting::find(1);
            return view('front.pubs', compact('setting', 'publications', 'pubcats'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function departments()
    {
        try {
            $departments = Department::where('status', 1)->latest()->take(500)->get();
            $setting = Setting::find(1);
            return view('front.departments', compact('setting', 'departments'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function departmentDetail($id)
    {
        try {
            $department = Department::find($id);
            $departments = Department::where('status', 1)->latest()->take(500)->get();
            $setting = Setting::find(1);
            return view('front.departmentdetail', compact('setting', 'department', 'departments'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function directorates()
    {
        try {
            $directorates = Directorate::where('status', 1)->latest()->take(500)->get();
            $setting = Setting::find(1);
            return view('front.directorates', compact('setting', 'directorates'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function directorateDetail($id)
    {
        try {
            $directorate = Directorate::find($id);
            $directorates = Directorate::where('status', 1)->latest()->take(500)->get();
            $services = Service::where('status', 1)->where('directorate_id', $directorate->id)->get();
            $setting = Setting::find(1);
            return view('front.directoratedetail', compact('setting', 'directorate', 'services', 'directorates'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function vacancies()
    {
        try {
            $vacancies = Vacancy::where('status', 1)->latest()->take(500)->get();
            $setting = Setting::find(1);
            return view('front.vacancies', compact('setting', 'vacancies'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function vacancyDetail($id)
    {
        try {
            $vacancy = Vacancy::find($id);
            $vacancies = Vacancy::where('status', 1)->latest()->take(500)->get();
            $setting = Setting::find(1);
            return view('front.vacancydetail', compact('setting', 'vacancy', 'vacancies'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function getApply($id)
    {
        try {
            $vacancy = Vacancy::find($id);
            $setting = Setting::find(1);
            return view('front.vacancyapply', compact('setting', 'vacancy'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function apply(Request $request, $id)
    {
        try {
            $this->validate($request, [
                'full_name' => 'string|max:255',
                'email' => 'required|email',
                'gender' => 'required',
                'age' => 'required|numeric',
                'year_of_graduation' => 'required|numeric',
                'field_of_study' => 'required',
                'educational_level' => 'required',
                'experience' => 'required',
                'phone' => ['required', new PhoneNumberRule],
                'attachments' => 'nullable|mimes:pdf,doc,docx|max:5048',
                'cv' => 'required|mimes:pdf,doc,docx|max:5048',
            ]);

            $application = new Application();
            $vacancy = Vacancy::find($id);
            $cv = $request->file('cv');
            $attachments = $request->file('attachments');

            if ($request->hasFile('cv')) {
                if (isset($cv)) {
                    $currentDate = Carbon::now()->toDateString();
                    $cvname = $id . '-' . $currentDate . '-' . uniqid() . '.' . $cv->getClientOriginalExtension();

                    if (!file_exists('uploads/Application')) {
                        mkdir('uploads/Application', 0777, true);
                    }
                    $cv->move('uploads/Application', $cvname);
                    $application->cv = $cvname;
                } else {
                    $cvname = "default.png";
                }
            }

            if ($request->hasFile('attachments')) {
                if (isset($attachments)) {
                    $currentDate = Carbon::now()->toDateString();
                    $attachmentsname = $id . '-' . $currentDate . '-' . uniqid() . '.' . $attachments->getClientOriginalExtension();

                    if (!file_exists('uploads/Application')) {
                        mkdir('uploads/Application', 0777, true);
                    }
                    $attachments->move('uploads/Application', $attachmentsname);
                    $application->arrachments = $attachmentsname; // kept as-is (DB field likely named this way)
                } else {
                    $attachmentsname = "default.png";
                }
            }

            $application->vacancy_id = $vacancy->id;
            $application->full_name = $request->full_name;
            $application->gender = $request->gender;
            $application->field_of_study = $request->field_of_study;
            $application->educational_level = $request->educational_level;
            $application->experience = $request->experience;
            $application->phone = $request->phone;
            $application->email = $request->email;
            $application->age = $request->age;
            $application->year_of_graduation = $request->year_of_graduation;
            $application->comment = $request->comment;
            $application->save();

            Alert::success('Success', 'Your application is submitted successfully!');
            return redirect()->route('front.vacancies');
        } catch (\Throwable $th) {
            Alert::error('Error', $th->getMessage());
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function serviceDetail($id)
    {
        try {
            $service = Service::find($id);
            $services = Service::where('status', 1)->where('directorate_id', $service->directorate_id)->get();
            $setting = Setting::find(1);
            return view('front.servicedetail', compact('setting', 'services', 'service'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function services()
    {
        try {
            $services = Service::where('status', 1)->latest()->get();
            $setting = Setting::find(1);
            return view('front.services', compact('setting', 'services'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function eventCategory($name)
    {
        try {
            $category = EventCategory::where('name', $name)->first();
            $events = Event::where('event_category_id', $category->id)->latest()->get();
            $categories = EventCategory::where('status', 1)->latest()->get();
            $setting = Setting::find(1);
            return view('front.events', compact('setting', 'events', 'categories'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function searchEvents(Request $request)
    {
        try {
            $query = $request->input('query');
            $events = Event::where('title', 'like', "%$query%")
                ->orWhere('description', 'like', "%$query%")
                ->orWhere('venue_location', 'like', "%$query%")
                ->orWhere('venue_name', 'like', "%$query%")
                ->orWhere('organiser_name', 'like', "%$query%")
                ->get();
            $categories = EventCategory::where('status', 1)->latest()->get();
            $setting = Setting::find(1);
            return view('front.events', compact('setting', 'events', 'categories'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function filterEvents(Request $request)
    {
        $categories = EventCategory::where('status', 1)->latest()->take(5)->get();
        $setting = Setting::find(1);
        $eventCategoryId = $request->input('event_category_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Build your query based on the filter parameters
        $events = Event::query();

        if ($eventCategoryId) {
            $events->where('event_category_id', $eventCategoryId);
        }

        if ($startDate) {
            $events->whereDate('start_date', '>=', $startDate);
        }

        if ($endDate) {
            $events->whereDate('end_date', '<=', $endDate);
        }

        $events = $events->get();
        return view('front.events', compact('events', 'categories', 'setting'));
    }

    public function blogDetail($id)
    {
        try {
            $blog = Blog::find($id);
            $comments = Comment::where('blog_id', $blog->id)
                ->where('status', 1)
                ->get();
            $categories = BlogCategory::where('status', 1)->latest()->get();
            $setting = Setting::find(1);
            return view('front.blogdetail', compact('setting', 'blog', 'categories', 'comments'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function addComment(Request $request, $slug)
    {
        try {
            $blog = Blog::where('blog_slug', $slug)->first();
            $this->validate($request, [
                'name' => 'string|max:255',
                'email' => 'required|email',
                'content' => 'required',
                // 'g-recaptcha-response' => ['required', new ReCaptcha]
            ]);

            Comment::create([
                'name' => $request->name,
                'email' => $request->email,
                'content' => $request->content,
                'blog_id' => $blog->id,
                'status' => '0',
            ]);

            Alert::success('Success', 'Your Message Successfully Delivered');
            return redirect()->back();
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function blogCategory($name)
    {
        try {
            $category = BlogCategory::where('name', $name)->first();
            $blogs = Blog::where('blog_category_id', $category->id)->latest()->get();
            $categories = BlogCategory::where('status', 1)->latest()->get();
            $setting = Setting::find(1);
            return view('front.blogs', compact('setting', 'blogs', 'categories'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function blogSearch(Request $request)
    {
        try {
            $searchTerm = $request->input('search');
            $blogs = Blog::where('blog_title', 'like', '%' . $searchTerm . '%')
                ->orWhere('description', 'like', '%' . $searchTerm . '%')
                ->paginate(10);
            $categories = BlogCategory::where('status', 1)->latest()->get();
            $setting = Setting::find(1);

            return view('front.blogs', compact('blogs', 'categories', 'setting'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function privacypolicy()
    {
        try {
            $setting = Setting::find(1);
            return view('front.privacypolicy', compact('setting'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function contact()
    {
        try {
            $setting = Setting::find(1);
            return view('front.contactus', compact('setting'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function galleries()
    {
        try {
            $setting = Setting::find(1);
            $galleries = Gallery::all();
            $years = Gallery::selectRaw('YEAR(created_at) as year')
                ->groupBy('year')
                ->pluck('year')
                ->toArray();
            return view('front.gallery', compact('setting', 'galleries', 'years'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function getGalleriesByYear($year)
    {
        try {
            $setting = Setting::find(1);
            $galleries = Gallery::whereYear('created_at', $year)
                ->latest()
                ->get();
            $years = Gallery::selectRaw('YEAR(created_at) as year')
                ->groupBy('year')
                ->pluck('year')
                ->toArray();
            return view('front.gallery', compact('setting', 'galleries', 'years'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function about()
    {
        try {
            $setting = Setting::find(1);
            $head = HeadMessage::find(1);
            $partners = Partner::all();
            $testimonies = Testimony::all();
            $histories = History::all();

            // Retrieve department data with associated directorates
            $departments = Department::where('status', 1)->with(['directorates' => function ($query) {
                $query->where('status', 1);
            }])->get();

            return view('front.about', compact('setting', 'partners', 'testimonies', 'histories', 'departments', 'head'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function faqs()
    {
        try {
            $setting = Setting::find(1);
            $faqs = Faq::all();
            return view('front.faq', compact('setting', 'faqs'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function contactusmessage(Request $request)
    {
        try {
            $this->validate($request, [
                'name' => 'string|max:255',
                'message' => 'required',
                'phone' => ['required', new PhoneNumberRule],
                'email' => 'required|email',
                // 'g-recaptcha-response' => ['required', new ReCaptcha],
            ]);

            Contact::create([
                'email' => $request->email,
                'name' => $request->name,
                'phone' => $request->phone,
                'message' => $request->message,
            ]);

            Alert::success('Success', 'Your Message Successfully Delivered');
            return redirect()->back();
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function privacy()
    {
        try {
            $setting = Setting::find(1);
            return view('front.privacy', compact('setting'));
        } catch (\Throwable $th) {
            toast($th, 'error');
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
}
