<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;

echo "Available Products:\n\n";

$products = Product::all();

foreach ($products as $product) {
    echo "ID: " . $product->id . "\n";
    echo "Name: " . $product->name . "\n";
    echo "Period Type: " . ($product->period_type ?? 'N/A') . "\n";
    echo "Interest: " . $product->interest . "%\n";
    echo "---\n";
}
