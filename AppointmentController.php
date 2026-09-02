<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\Doctor;

class AppointmentController extends Controller
{
    // Show appointment form (user)
    public function create()
    {
        $doctors = Doctor::all(); // fetch all doctors
        return view('front.appointment', compact('doctors'));
    }

    // Handle form submission (user)
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required',
            'doctor' => 'required|exists:doctors,id',
            'date' => 'required|date',
            'time' => 'required',
            'message' => 'nullable|string',
        ]);

        Appointment::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'doctor_id' => $request->doctor,
            'date' => $request->date,
            'time' => $request->time,
            'message' => $request->message,
        ]);

        return redirect()->route('appointment.page')
                         ->with('success', __('Your appointment has been submitted successfully!'));
    }

    // Admin: List all appointments
    public function index()
    {
        $appointments = Appointment::with('doctor')->orderBy('date', 'desc')->orderBy('time')->get();
        return view('admin.appointments.index', compact('appointments'));
    }

    // Admin: Approve appointment
    public function approve($id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->update(['status' => 'approved']);
        return back()->with('success', __('Appointment approved.'));
    }



    // Admin: Delete appointment
    public function destroy($id)
    {
        Appointment::findOrFail($id)->delete();
        return back()->with('success', __('Appointment deleted.'));
    }
}
