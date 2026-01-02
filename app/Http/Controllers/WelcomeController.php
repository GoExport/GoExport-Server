<?php

namespace App\Http\Controllers;

use App\Models\Export;
use App\Models\ExportSetting;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class WelcomeController extends Controller
{
    /**
     * Display the welcome page with paginated exports.
     */
    public function index(): View|Response
    {
        // Check if the homepage display is enabled (default to true if not set)
        $homePageEnabled = ExportSetting::get('show_homepage', true);

        // If homepage is disabled, return a simple placeholder or 404
        if (!$homePageEnabled) {
            return abort(404);
        }

        // Fetch paginated exports
        $exports = Export::paginate(12);

        return view('welcome', [
            'exports' => $exports,
        ]);
    }
}
