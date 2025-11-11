<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\ProductCharge;

echo "=== PRODUCT CHARGES CHECK ===\n\n";

$product = Product::find(8);
if ($product) {
    echo "Product: {$product->name}\n";
    echo "Product ID: {$product->id}\n\n";
    
    echo "Charges:\n";
    $charges = ProductCharge::where('product_id', 8)->get();
    
    if ($charges->count() > 0) {
        foreach ($charges as $charge) {
            echo "\nCharge ID: {$charge->id}\n";
            echo "Name: {$charge->name}\n";
            echo "Type: {$charge->type}\n";
            echo "Value (raw): ";
            var_dump($charge->getAttributes()['value'] ?? 'NOT SET');
            echo "Is Active: {$charge->isactive}\n";
            echo "---\n";
        }
    } else {
        echo "No charges found for this product.\n";
    }
} else {
    echo "Product 8 not found.\n";
}
