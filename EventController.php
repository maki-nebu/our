<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\Event;
use App\Models\EventCategory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use RealRashid\SweetAlert\Facades\Alert;

class EventController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:event_access', ['only' => ['index', 'show']]);
        $this->middleware('permission:event_create', ['only' => ['create', 'store']]);
        $this->middleware('permission:event_edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:event_delete', ['only' => ['destroy']]);
    }
    
    public function index()
    {
        try {
            $events = Event::all();
            return view('admin.event.index', compact('events'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function create()
    {
        try {
            $eventcategories = EventCategory::where('status', 1)->latest()->get();
            return view('admin.event.create', compact('eventcategories'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function edit($id)
    {
        try {
            $event = Event::find($id);
            $eventcategories = EventCategory::where('status', 1)->latest()->get();
            return view('admin.event.edit', compact('event', 'eventcategories'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function store(Request $request)
    {
        try {
            $yesterday = Carbon::now()->subDay();

            $this->validate($request, [
                'title' => 'required',
                'description' => 'required',
                'event_category_id' => 'required',
                'venue_location' => 'required',
                'organiser_name' => 'required',
                'start_date' => ['required', 'date', 'after_or_equal:' . $yesterday->format('Y-m-d')],
                'end_date' => ['required', 'date', 'after:start_date'],
                'event_images.*' => 'required|mimes:jpeg,jpg,bmp,png,svg',
            ]);


            $images = [];
            if ($request->hasfile('event_images')) {
                foreach ($request->file('event_images') as $file) {
                    $imageName = uniqid() . '.' . $file->getClientOriginalName();
                    $file->move(public_path('uploads/Event'), $imageName);
                    $images[] = $imageName;
                }
            } else {
                $images = "default.png";
            }
            $event = Event::create([
                'title' => $request->title,
                'slug' => Str::slug($request->title),
                'description' => $request->description,
                'event_category_id' => $request->event_category_id,
                'venue_location' => $request->venue_location,
                'venue_name' => $request->venue_name,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'event_images' => $images,
                'organiser_name' => $request->organiser_name,
            ]);
            $event->save();

            $log = new Log();
            $log->action = "A New Event Created";
            $log->user_id = Auth::user()->id;
            $log->save();
            toast('Event Successfully Saved!', 'success');
            return redirect()->route('admin.events');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function update(Request $request, $id)
    {
        try {
            $yesterday = Carbon::now()->subDay();
            $this->validate($request, [
                'title' => 'required',
                'description' => 'required',
                'event_category_id' => 'required',
                'venue_location' => 'required',
                'organiser_name' => 'required',
                'start_date' => ['required', 'date', 'after_or_equal:' . $yesterday->format('Y-m-d')],
                'end_date' => ['required', 'date', 'after:start_date'],
                'event_images.*' => 'nullable|mimes:jpeg,jpg,bmp,png,svg',
            ]);
            $event = Event::find($id);

            $images = [];
            if ($request->hasfile('event_images')) {
                foreach ($request->file('event_images') as $file) {
                    $slug = Str::slug($request->title);
                    $currentDate = Carbon::now()->toDateString();
                    $imageName = $slug . '-' . $currentDate . '-' . uniqid() . '.' . $file->getClientOriginalName();
                    $file->move(public_path('uploads/Event'), $imageName);
                    $images[] = $imageName;
                }
                $event->event_images = $images;
            }
            $event->title = $request->title;
            $event->slug = Str::slug($request->title);
            $event->description = $request->description;
            $event->event_category_id = $request->event_category_id;
            $event->venue_location = $request->venue_location;
            $event->venue_name = $request->venue_name;
            $event->start_date = $request->start_date;
            $event->end_date = $request->end_date;
            $event->organiser_name = $request->organiser_name;
            $event->save();

            $log = new Log();
            $log->action = "An event information updated";
            $log->user_id = Auth::user()->id;
            $log->save();
            return redirect()->route('admin.events')->with('successMsg', 'Event Updated!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function restore(int $id)
    {
        try {
            $event = Event::onlyTrashed()->findOrFail($id);
            $event->restore();
            toast('Event Restored!', 'success');
            return redirect()->back();
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function onlyTrashed()
    {
        try {
            $events = Event::onlyTrashed()->get();
            return view('admin.event.trashed', compact('events'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function permanent(Request $request, $id)
    {
        try {
            Event::onlyTrashed()->find($id)->forceDelete();
            $log = new Log();
            $log->action = " An Event deleted";
            $log->user_id = Auth::user()->id;
            $log->save();
            toast('Permanently Deleted Succesfully!', 'success');
            return redirect()->back();
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function delete(Request $request, $id)
    {
        try {
            $event = Event::find($id);
            $event->delete();
            $log = new Log();
            $log->action = " An Event deleted";
            $log->user_id = Auth::user()->id;
            $log->save();
            toast('Permanently Deleted Succesfully!', 'success');
            return redirect()->back();
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
}
