<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ClassifiedListingController extends Controller
{
    public function index(): View
    {
        return view('partner.classifieds.index');
    }

    public function show(string $id): View
    {
        return view('partner.classifieds.show', ['listingId' => $id]);
    }
}
