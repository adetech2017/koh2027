<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Serves the two React apps. Every non-API path on the fleet subdomain lands here and
 * React Router takes it from there.
 */
class SpaController extends Controller
{
    public function beneficiary(): View
    {
        return view('fleet.beneficiary');
    }

    public function admin(): View
    {
        return view('fleet.admin');
    }
}
