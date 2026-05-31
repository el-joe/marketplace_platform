<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function index(): View
    {
        return view('portal.home');
    }

    public function faq(): View
    {
        return view('portal.faq');
    }

    public function howItWorks(): View
    {
        return view('portal.how-it-works');
    }

    public function fulfillment(): View
    {
        return view('portal.fulfillment');
    }

    public function smartTools(): View
    {
        return view('portal.smart-tools');
    }
}
