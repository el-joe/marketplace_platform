<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FinanceController extends Controller
{
    public function transactions(Request $request)
    {
        $vendor = Auth::guard('vendor')->user();

        return view('partner.finance.transactions', compact('vendor'));
    }
}
