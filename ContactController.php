<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Log;
use App\Models\Testimony;
use App\Rules\ReCaptcha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Setting;



class ContactController extends Controller
{
    public function __construct()
    {
        // Protect only admin-related routes
        $this->middleware('permission:contact_access', ['only' => ['show']]);
        $this->middleware('permission:contact_create', ['only' => ['testimony', 'destroy']]);
        $this->middleware('permission:contact_edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:contact_delete', ['only' => ['destroy']]);
    }

    // ---------------- Public methods for front-end ----------------

    // Show the contact form (public)
public function index()
{
    $settings = \App\Models\Setting::first();

    return view('front.contact', compact('settings'));
}

    // Submit contact form (public)
    public function store(Request $request)
    {
        try {
            $this->validate($request, [
                'name' => 'required',
                'email' => 'required|email',
                'message' => 'required',
                'g-recaptcha-response' => ['required', new ReCaptcha]
            ]);

            $contact = new Contact();
            $contact->name = $request->name;
            $contact->email = $request->email;
            $contact->phone = $request->phone;
            $contact->message = $request->message;
            $contact->save();

            toast('Thank you for your message! We will contact you soon!', 'success');

            return redirect()->back();
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    // ---------------- Admin methods ----------------

    // List all messages (admin)
    public function show(Request $request, $id)
    {
        try {
            $contact = Contact::findOrFail($id);
            return view('admin.contact.show', compact('contact'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    // List all messages (admin)
public function adminIndex()
{
    try {
        // Get all messages, excluding soft-deleted ones
        $contacts = Contact::latest()->get();
        return view('admin.contact.index', compact('contacts'));
    } catch (\Throwable $th) {
        return redirect()->back()->with('infoMsg', $th->getMessage());
    }
}

public function submit(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'message' => 'required|string',
        ]);

        Contact::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'message' => $request->message,
        ]);

        return redirect()->back()->with('success', 'Your message has been sent successfully!');
    }

    // Submit testimony (admin)
    public function testimony(Request $request)
    {
        try {
            $this->validate($request, [
                'fullname' => 'required',
                'content' => 'required',
                'title' => 'required',
                'image' => 'required|mimes:jpg,jpeg,png,bmp,tiff|max:4096',
            ]);

            $image = $request->file('image');
            $slug = Str::slug($request->title);
            if (isset($image)) {
                $currentDate = Carbon::now()->toDateString();
                $imagename = $slug . '-' . $currentDate . '-' . uniqid() . '.' . $image->getClientOriginalExtension();

                if (!file_exists('uploads/Testimony')) {
                    mkdir('uploads/Testimony', 0777, true);
                }
                $image->move('uploads/Testimony', $imagename);
            } else {
                $imagename = "default.png";
            }

            $testimony = new Testimony();
            $testimony->fullname = $request->fullname;
            $testimony->content = $request->content;
            $testimony->title = $request->title;
            $testimony->image = $imagename;
            $testimony->is_enabled = 0;
            $testimony->save();

            toast('Thank you for your testimony! We will review it soon!', 'success');
            return redirect()->back();

        } catch (\Throwable $th) {
            toast('Something went wrong! Please try again.', 'warning');
            return redirect()->back();
        }
    }

    // Delete a contact (admin)
    public function destroy(Request $request, $id)
    {
        try {
            $contact = Contact::findOrFail($id);
            $contact->delete();

            $log = new Log();
            $log->action = "A contact information deleted";
            $log->user_id = Auth::user()->id;
            $log->save();

            return redirect()->back()->with('successMsg', 'Contact successfully deleted');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    // Subscribe (public)
    public function subscribe(Request $request)
    {
        try {
            $this->validate($request, [
                'email' => 'required|email',
                'g-recaptcha-response' => ['required', new ReCaptcha]
            ]);

            $contact = new Contact();
            $contact->name = "Subscription";
            $contact->email = $request->email;
            $contact->phone = "Subscription";
            $contact->message = "Subscription";
            $contact->save();

            toast('Thank you for your subscription!', 'success');
            return redirect()->back();
        } catch (\Throwable $th) {
            toast($th->getMessage(), 'error');
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
}
