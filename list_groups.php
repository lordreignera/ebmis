<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Group;

echo "Total groups: " . Group::count() . "\n";

$groups = Group::with(['branch', 'addedBy'])->get();

foreach($groups as $group) {
    echo "\nGroup Details:\n";
    echo "- ID: {$group->id}\n";
    echo "- Code: {$group->code}\n";
    echo "- Name: {$group->name}\n";
    echo "- Sector: {$group->sector}\n"; 
    echo "- Type: " . ($group->type == 1 ? 'Preliminary (Open)' : 'Incubation (Closed)') . "\n";
    echo "- Verified: " . ($group->verified ? 'Yes' : 'No') . "\n";
    echo "- Branch: " . ($group->branch ? $group->branch->name : 'No branch') . "\n";
    echo "- Address: {$group->address}\n";
    echo "- Inception Date: {$group->inception_date}\n";
    echo "- Date Created: {$group->datecreated}\n";
}