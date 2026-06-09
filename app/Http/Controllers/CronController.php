<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class CronController extends Controller
{
    public function runScheduler(Request $request)
    {
        $expectedSecret = (string) config('app.cron_secret', '');
        $providedSecret = (string) $request->query('token', '');

        if ($expectedSecret === '' || $providedSecret === '' || !hash_equals($expectedSecret, $providedSecret)) {
            abort(403, 'Unauthorized');
        }
        
        // Run the scheduler
        Artisan::call('schedule:run');
        
        return response()->json([
            'success' => true,
            'message' => 'Scheduler executed',
            'output' => Artisan::output()
        ]);
    }
}
