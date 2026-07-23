<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('purchase_requests', 'purchase_number')) {
            Schema::table('purchase_requests', function (Blueprint $table) {
                $table->string('purchase_number')->nullable()->after('workflow_state_id')->index();
            });

            // Backfill existing records
            $records = DB::table('purchase_requests')
                ->select('purchase_requests.id', 'purchase_requests.created_at', 'locations.code as branch_code')
                ->leftJoin('locations', 'purchase_requests.branch_id', '=', 'locations.id')
                ->orderBy('purchase_requests.id', 'asc')
                ->get();

            $dateSeqMap = [];

            foreach ($records as $record) {
                $branchCode = $record->branch_code ?: 'MAIN';
                $createdAt = $record->created_at ? \Carbon\Carbon::parse($record->created_at) : now();
                $dateKey = $createdAt->format('Y-m-d');

                if (!isset($dateSeqMap[$dateKey])) {
                    $dateSeqMap[$dateKey] = 0;
                }
                $dateSeqMap[$dateKey]++;
                $seq = $dateSeqMap[$dateKey];

                $purchaseNumber = 'PR-' . $branchCode . '/' . $createdAt->format('ymd') . str_pad($seq, 3, '0', STR_PAD_LEFT);

                DB::table('purchase_requests')
                    ->where('id', $record->id)
                    ->update(['purchase_number' => $purchaseNumber]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('purchase_requests', 'purchase_number')) {
            Schema::table('purchase_requests', function (Blueprint $table) {
                $table->dropColumn('purchase_number');
            });
        }
    }
};
