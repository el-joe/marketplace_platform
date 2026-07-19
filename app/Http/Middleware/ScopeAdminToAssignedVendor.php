<?php

namespace App\Http\Middleware;

use App\Models\Vendor;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ScopeAdminToAssignedVendor
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = Auth::guard('admin')->user();

        if ($admin && $admin->hasRole('vendor_relations_admin')) {
            $vendorIds = Vendor::where('account_manager_admin_id', $admin->id)->pluck('id')->all();

            $request->attributes->set('is_scoped_admin', true);
            $request->attributes->set('scoped_vendor_ids', $vendorIds);
        } else {
            $request->attributes->set('is_scoped_admin', false);
            $request->attributes->set('scoped_vendor_ids', null);
        }

        return $next($request);
    }
}
