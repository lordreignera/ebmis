<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LocationController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum')->name('api.user');

// Uganda Locations API
Route::prefix('locations')->middleware('throttle:120,1')->name('api.locations.')->group(function () {
    Route::get('/districts', [LocationController::class, 'getDistricts'])->name('districts');
    Route::get('/subcounties/{districtId}', [LocationController::class, 'getSubcounties'])->name('subcounties');
    Route::get('/parishes/{subcountyId}', [LocationController::class, 'getParishes'])->name('parishes');
    Route::get('/villages/{parishId}', [LocationController::class, 'getVillages'])->name('villages');
    Route::get('/search', [LocationController::class, 'search'])->name('search');
});
