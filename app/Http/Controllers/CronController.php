<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class CronController extends Controller
{
    public function runScheduler(Request $request)
    {
        // Security: Check secret token
        $secret = $request->query('token');
        
        if ($secret !== config('app.cron_secret')) {
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
