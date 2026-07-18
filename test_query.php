<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$datesSubquery = DB::table('purchase_requests')
    ->selectRaw('DATE(created_at) as id')
    ->whereNull('deleted_at')
    ->union(
        DB::table('daily_price_histories')->selectRaw('DATE(created_at) as id')
    );

$query = \App\Models\DailyPriceHistory::query()
    ->fromSub($datesSubquery, 'daily_price_histories')
    ->selectRaw('daily_price_histories.id as id, daily_price_histories.id as missing_date')
    ->whereNotExists(function ($query) {
        $query->selectRaw(1)
            ->from('announcement_gold_prices')
            ->whereRaw('DATE(announcement_gold_prices.announcement_datetime) = daily_price_histories.id');
    })
    ->groupBy('daily_price_histories.id');

echo "SQL: " . $query->toSql() . "\n\n";

try {
    $results = $query->get();
    echo "Total rows: " . $results->count() . "\n";
    foreach ($results as $row) {
        echo "id: {$row->id}, missing_date: {$row->missing_date}\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}




