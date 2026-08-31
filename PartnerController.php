<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\Partner;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PartnerController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:partner_access', ['only' => ['index', 'show']]);
        $this->middleware('permission:partner_create', ['only' => ['create', 'store']]);
        $this->middleware('permission:partner_edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:partner_delete', ['only' => ['destroy', 'permanent', 'delete']]);
    }
    
    public function index()
    {
        try {
            $partners = Partner::orderByDesc('updated_at')->get();
            return view('admin.partner.index', compact('partners'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function create()
    {
        try {
            return view('admin.partner.create');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function edit($id)
    {
        try {
            $partner = Partner::find($id);
            return view('admin.partner.edit', compact('partner'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function store(Request $request)
    {
        try {
            $this->validate($request, [
                'name' => 'required',
                'link' => 'required',
                'image' => 'required|mimes:jpeg,jpg,bmp,png',
            ]);
            $image = $request->file('image');
            $slug = Str::slug($request->name);

            if (isset($image)) {
                $currentDate = Carbon::now()->toDateString();
                $imagename = $slug . '-' . $currentDate . '-' . uniqid() . '.' . $image->getClientOriginalExtension();

                if (!file_exists('uploads/Partner')) {
                    mkdir('uploads/Partner', 0777, true);
                }
                $image->move('uploads/Partner', $imagename);
            } else {
                $imagename = "default.png";
            }
            $partner = Partner::create([
                'name' => $request->name,
                'link' => $request->link,
                'image' => $imagename,
            ]);
            $partner->save();

            $log = new Log();
            $log->action = "A New Partner Created";
            $log->user_id = Auth::user()->id;
            $log->save();
            return redirect()->route('admin.partners')->with('successMsg', 'Partner Successfully Saved!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function update(Request $request, $id)
    {
        try {
            $this->validate($request, [
                'name' => 'required',
                'link' => 'required',
            ]);
            $partner = Partner::find($id);

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $slug = Str::slug($request->name);
                if (isset($image)) {
                    $currentDate = Carbon::now()->toDateString();
                    $imagename = $slug . '-' . $currentDate . '-' . uniqid() . '.' . $image->getClientOriginalExtension();

                    if (!file_exists('uploads/Partner')) {
                        mkdir('uploads/Partner', 0777, true);
                    }
                    $image->move('uploads/Partner', $imagename);
                    $partner->image = $imagename;
                }
            }
            $partner->name = $request->name;
            $partner->link = $request->link;

            $partner->save();
            $log = new Log();
            $log->action = "A partner information updated";
            $log->user_id = Auth::user()->id;
            $log->save();
            return redirect()->route('admin.partners')->with('successMsg', 'Partner Updated!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function restore(int $id)
    {
        try {
            $partner = Partner::onlyTrashed()->findOrFail($id);
            $partner->restore();
            $log = new Log();
            $log->action = "A Partner restored";
            $log->user_id = Auth::user()->id;
            $log->save();
            return redirect()->back()->with('successMsg', 'Partner Restored Succesfully!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function onlyTrashed()
    {
        try {
            $partners = Partner::onlyTrashed()->get();
            return view('admin.partner.trashed', compact('partners'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function permanent(Request $request, $id)
    {
        try {
            Partner::onlyTrashed()->find($id)->forceDelete();
            $log = new Log();
            $log->action = "A Partner deleted";
            $log->user_id = Auth::user()->id;
            $log->save();
            return redirect()->back()->with('successMsg', 'Permanently Deleted Succesfully!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function delete(Request $request, $id)
    {
        try {
            $partner = Partner::find($id);
            $partner->delete();
            $log = new Log();
            $log->action = "A Partner deleted";
            $log->user_id = Auth::user()->id;
            $log->save();
            return redirect()->back()->with('successMsg', 'Deleted Succesfully!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
}
