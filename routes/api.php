<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LocationController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Uganda Locations API
Route::prefix('locations')->group(function () {
    Route::get('/districts', [LocationController::class, 'getDistricts']);
    Route::get('/subcounties/{districtId}', [LocationController::class, 'getSubcounties']);
    Route::get('/parishes/{subcountyId}', [LocationController::class, 'getParishes']);
    Route::get('/villages/{parishId}', [LocationController::class, 'getVillages']);
    Route::get('/search', [LocationController::class, 'search']);
});
