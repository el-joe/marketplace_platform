<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $vendor = auth()->guard('vendor')->user()->vendor;

        return view('partner.dashboard', compact('vendor'));
    }
}
