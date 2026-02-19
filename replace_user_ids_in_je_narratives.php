<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\JournalEntry;
use App\Models\User;

$log = __DIR__ . '/storage/logs/replace_user_ids_in_je_narratives.log';
$csv = __DIR__ . '/storage/je_narrative_user_replacements.csv';

file_put_contents($log, "\n---- Run at " . date('Y-m-d H:i:s') . " ----\n", FILE_APPEND);

// Patterns to look for. We'll capture the label + numeric id
$patterns = [
    '/(Processed by:\s*)(\d+)\b/i',
    '/(Disbursed by:\s*)(\d+)\b/i',
    '/(Assigned to:\s*)(\d+)\b/i',
    '/(Posted by:\s*)(\d+)\b/i',
    '/(Created by:\s*)(\d+)\b/i'
];

$query = JournalEntry::where(function($q) {
    $q->where('narrative', 'like', '%Processed by:%')
      ->orWhere('narrative', 'like', '%Disbursed by:%')
      ->orWhere('narrative', 'like', '%Assigned to:%')
      ->orWhere('narrative', 'like', '%Posted by:%')
      ->orWhere('narrative', 'like', '%Created by:%');
});

$entries = $query->get();

if ($entries->isEmpty()) {
    echo "No narratives matched patterns.\n";
    exit(0);
}

$out = fopen($csv, 'w');
fputcsv($out, ['je_id','journal_number','old_narrative','new_narrative']);

$updated = 0;
foreach ($entries as $e) {
    $old = $e->narrative ?? '';
    $new = $old;

    $callback = function($matches) use ($log) {
        $label = $matches[1];
        $id = intval($matches[2]);
        $user = User::find($id);
        if ($user) {
            // prefer first/last name fields, fall back to name or username
            $fullname = trim((($user->fname ?? '') . ' ' . ($user->lname ?? '')));
            if (empty($fullname)) {
                $fullname = $user->name ?? ($user->username ?? 'user_'.$id);
            }
            return $label . $fullname;
        }
        // if user not found, keep original id but annotate
        return $label . $matches[2] . ' (unknown)';
    };

    foreach ($patterns as $pat) {
        $new = preg_replace_callback($pat, $callback, $new);
    }

    if ($new !== $old) {
        try {
            $e->narrative = $new;
            $e->save();
            fputcsv($out, [$e->id, $e->journal_number, $old, $new]);
            file_put_contents($log, "Updated JE {$e->journal_number} ({$e->id})\n", FILE_APPEND);
            $updated++;
        } catch (Exception $ex) {
            file_put_contents($log, "Error updating JE {$e->journal_number}: " . $ex->getMessage() . "\n", FILE_APPEND);
        }
    }
}

fclose($out);

echo "Completed. Updated {$updated} journal entries. CSV: storage/je_narrative_user_replacements.csv. Log: {$log}\n";
exit(0);

?>
