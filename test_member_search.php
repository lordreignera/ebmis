<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Member;
use Illuminate\Http\Request;

echo "=== Testing Member Search Filters ===\n\n";

// Test 1: Search for "Otim Benard" by first name
echo "Test 1: Searching for 'Otim' in first name...\n";
$query1 = Member::query();
$search = "Otim";
$query1->where(function($q) use ($search) {
    $q->where('fname', 'like', "%{$search}%")
      ->orWhere('lname', 'like', "%{$search}%")
      ->orWhere('mname', 'like', "%{$search}%")
      ->orWhere('code', 'like', "%{$search}%")
      ->orWhere('nin', 'like', "%{$search}%")
      ->orWhere('contact', 'like', "%{$search}%")
      ->orWhere('email', 'like', "%{$search}%");
});
$results1 = $query1->get();
echo "Found {$results1->count()} member(s):\n";
foreach ($results1 as $member) {
    echo "  - ID: {$member->id}, Code: {$member->code}, Name: {$member->fname} {$member->mname} {$member->lname}\n";
    echo "    Type: {$member->member_type}, Status: {$member->status}, Contact: {$member->contact}\n";
}
echo "\n";

// Test 2: Search for "Benard" in last name
echo "Test 2: Searching for 'Benard' in last name...\n";
$query2 = Member::query();
$search = "Benard";
$query2->where(function($q) use ($search) {
    $q->where('fname', 'like', "%{$search}%")
      ->orWhere('lname', 'like', "%{$search}%")
      ->orWhere('mname', 'like', "%{$search}%")
      ->orWhere('code', 'like', "%{$search}%")
      ->orWhere('nin', 'like', "%{$search}%")
      ->orWhere('contact', 'like', "%{$search}%")
      ->orWhere('email', 'like', "%{$search}%");
});
$results2 = $query2->get();
echo "Found {$results2->count()} member(s):\n";
foreach ($results2 as $member) {
    echo "  - ID: {$member->id}, Code: {$member->code}, Name: {$member->fname} {$member->mname} {$member->lname}\n";
    echo "    Type: {$member->member_type}, Status: {$member->status}, Contact: {$member->contact}\n";
}
echo "\n";

// Test 3: Search for "Otim Benard" (full name)
echo "Test 3: Searching for 'Otim Benard' (combined)...\n";
$query3 = Member::query();
$search = "Otim Benard";
$query3->where(function($q) use ($search) {
    $q->where('fname', 'like', "%{$search}%")
      ->orWhere('lname', 'like', "%{$search}%")
      ->orWhere('mname', 'like', "%{$search}%")
      ->orWhere('code', 'like', "%{$search}%")
      ->orWhere('nin', 'like', "%{$search}%")
      ->orWhere('contact', 'like', "%{$search}%")
      ->orWhere('email', 'like', "%{$search}%");
});
$results3 = $query3->get();
echo "Found {$results3->count()} member(s):\n";
foreach ($results3 as $member) {
    echo "  - ID: {$member->id}, Code: {$member->code}, Name: {$member->fname} {$member->mname} {$member->lname}\n";
    echo "    Type: {$member->member_type}, Status: {$member->status}, Contact: {$member->contact}\n";
}
echo "\n";

// Test 4: Filter by member_type = 2 (like the URL parameter)
echo "Test 4: Filtering by member_type = 2...\n";
$query4 = Member::query();
$query4->where('member_type', 2);
$results4 = $query4->get();
echo "Found {$results4->count()} member(s) with member_type = 2\n";
if ($results4->count() > 0) {
    echo "First 5 members:\n";
    foreach ($results4->take(5) as $member) {
        echo "  - ID: {$member->id}, Code: {$member->code}, Name: {$member->fname} {$member->mname} {$member->lname}\n";
        echo "    Type: {$member->member_type}, Status: {$member->status}\n";
    }
}
echo "\n";

// Test 5: Combined search "Otim" + member_type = 2
echo "Test 5: Searching for 'Otim' with member_type = 2...\n";
$query5 = Member::query();
$search = "Otim";
$query5->where(function($q) use ($search) {
    $q->where('fname', 'like', "%{$search}%")
      ->orWhere('lname', 'like', "%{$search}%")
      ->orWhere('mname', 'like', "%{$search}%")
      ->orWhere('code', 'like', "%{$search}%")
      ->orWhere('nin', 'like', "%{$search}%")
      ->orWhere('contact', 'like', "%{$search}%")
      ->orWhere('email', 'like', "%{$search}%");
});
$query5->where('member_type', 2);
$results5 = $query5->get();
echo "Found {$results5->count()} member(s):\n";
foreach ($results5 as $member) {
    echo "  - ID: {$member->id}, Code: {$member->code}, Name: {$member->fname} {$member->mname} {$member->lname}\n";
    echo "    Type: {$member->member_type}, Status: {$member->status}, Contact: {$member->contact}\n";
}
echo "\n";

// Test 6: Check if "Otim Benard" exists as individual or group member
echo "Test 6: Looking for exact 'Otim Benard' pattern...\n";
$query6 = Member::query();
$query6->where(function($q) {
    $q->where('fname', 'like', '%Otim%')
      ->where(function($sub) {
          $sub->where('lname', 'like', '%Benard%')
             ->orWhere('mname', 'like', '%Benard%');
      });
})
->orWhere(function($q) {
    $q->where('lname', 'like', '%Otim%')
      ->where(function($sub) {
          $sub->where('fname', 'like', '%Benard%')
             ->orWhere('mname', 'like', '%Benard%');
      });
});
$results6 = $query6->get();
echo "Found {$results6->count()} member(s) matching 'Otim Benard' pattern:\n";
foreach ($results6 as $member) {
    echo "  - ID: {$member->id}, Code: {$member->code}, Name: {$member->fname} {$member->mname} {$member->lname}\n";
    echo "    Type: {$member->member_type}, Status: {$member->status}, Contact: {$member->contact}, Email: {$member->email}\n";
    echo "    Branch ID: {$member->branch_id}\n";
}
echo "\n";

echo "=== Test Complete ===\n";
