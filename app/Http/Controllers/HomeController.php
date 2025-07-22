<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\DownloadLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     */
    public function index() : View
    {
        return view('home');
    }
}
