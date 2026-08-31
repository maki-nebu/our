<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\User;
use Illuminate\Http\Request;

class LogController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:log_access', ['only' => ['index']]);
        $this->middleware('permission:log_create', ['only' => ['create', 'store']]);
        $this->middleware('permission:log_show', ['only' => ['show']]);
        $this->middleware('permission:log_delete', ['only' => ['destroy']]);
    }

    public function index()
    {
        try {
            $logs = Log::orderByDesc('created_at')->get();
            return view('admin.log.index', compact('logs'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function userLog($id)
    {
        try {
            $uLogs = Log::where('user_id', $id)->get();
            $user = User::find($id);
            return view('admin.log.ulog', compact('uLogs', 'user'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
}
