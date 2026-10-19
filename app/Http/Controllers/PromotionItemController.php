<?php

namespace App\Http\Controllers;

use App\Models\PromotionItem;
use App\Services\PromotionItemImportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PromotionItemController extends Controller
{
    /**
     * Display a listing of the promotion items with pagination (10 per page) and search.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search');
        $branchId = $request->query('branch_id');
        $soldStatus = $request->query('sold_status'); // 'all', 'unsold', 'sold'

        $query = PromotionItem::query()
            ->with(['soldItem', 'branch'])
            ->search($search);

        if ($branchId !== null && $branchId !== '') {
            $query->where('branch_id', $branchId);
        }

        if ($soldStatus === 'sold') {
            $query->whereHas('soldItem');
        } elseif ($soldStatus === 'unsold') {
            $query->whereDoesntHave('soldItem');
        }

        $items = $query->latest('id')
            ->paginate(10)
            ->withQueryString();

        $latestDailyPrice = \App\Models\DailyPriceHistory::latest('id')->first();
        $latestGoldPrice = $latestDailyPrice ? (float) $latestDailyPrice->gold_price : 0;
        $latestPriceUpdatedAt = $latestDailyPrice?->updated_at ?? $latestDailyPrice?->created_at;

        $branches = \App\Models\Branch::orderBy('name')->get(['id', 'name', 'code']);

        return Inertia::render('Promotions/Index', [
            'items' => $items,
            'branches' => $branches,
            'filters' => [
                'search' => $search ?? '',
                'branch_id' => $branchId ?? '',
                'sold_status' => $soldStatus ?? 'all',
            ],
            'latestGoldPrice' => $latestGoldPrice,
            'latestPriceHistoryId' => $latestDailyPrice?->id,
            'latestPriceUpdatedAt' => $latestPriceUpdatedAt ? $latestPriceUpdatedAt->toIso8601String() : null,
            'importErrors' => session('importErrors', []),
            'canImport' => $request->user()?->can('promotion.import') || $request->user()?->hasRole('Super Admin'),
        ]);
    }

    /**
     * Import promotion items from an uploaded Excel or CSV file.
     */
    public function import(Request $request, PromotionItemImportService $importService)
    {
        // Permission check
        if (!($request->user()?->can('promotion.import') || $request->user()?->hasRole('Super Admin'))) {
            abort(403, 'Unauthorized to import promotion items.');
        }

        $request->validate([
            'file' => ['required', 'file', 'max:20480'], // max 20MB
        ]);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['xlsx', 'csv', 'txt'])) {
            return back()->with('error', 'Only .xlsx or .csv files are supported.');
        }

        try {
            $result = $importService->import($file);

            return back()->with(
                'success',
                "Successfully imported {$result['imported']} items. ({$result['skipped']} group/summary rows ignored)."
            );
        } catch (\App\Exceptions\PromotionImportValidationException $e) {
            return back()->with([
                'error' => "Import stopped: Found {$e->getMessage()}",
                'importErrors' => $e->getErrors(),
            ]);
        } catch (\Throwable $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Store sold promotion items with sale voucher reference number.
     */
    public function storeSold(Request $request)
    {
        $validated = $request->validate([
            'reference_number' => ['required', 'string', 'max:255'],
            'daily_price_history_id' => ['nullable', 'integer', 'exists:daily_price_histories,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:promotion_items,id'],
            'items.*.promotion_price' => ['required', 'numeric', 'min:0'],
        ]);

        $itemIds = collect($validated['items'])->pluck('id')->toArray();

        // Check if any items are already marked as sold
        $alreadySold = \App\Models\PromotionSoldItem::whereIn('promotion_item_id', $itemIds)->pluck('promotion_item_id')->toArray();
        if (!empty($alreadySold)) {
            return back()->with('error', 'Selected item(s) have already been sold.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $request) {
            foreach ($validated['items'] as $itemData) {
                \App\Models\PromotionSoldItem::create([
                    'promotion_item_id' => $itemData['id'],
                    'user_id' => $request->user()?->id,
                    'promotion_price' => $itemData['promotion_price'],
                    'reference_number' => $validated['reference_number'],
                    'daily_price_history_id' => $validated['daily_price_history_id'] ?? null,
                ]);
            }
        });

        return back()->with('success', 'Sale recorded successfully! Items have been marked as sold.');
    }

    /**
     * Get all sold items belonging to the same voucher reference number.
     */
    public function getVoucherItems(string $referenceNumber)
    {
        $soldItems = \App\Models\PromotionSoldItem::with('promotionItem')
            ->where('reference_number', $referenceNumber)
            ->get();

        return response()->json([
            'reference_number' => $referenceNumber,
            'items' => $soldItems,
        ]);
    }

    /**
     * Cancel/delete sold items, revert their status, and log reasons.
     */
    public function cancelSold(Request $request)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3'],
            'sold_item_ids' => ['required', 'array', 'min:1'],
            'sold_item_ids.*' => ['required', 'integer', 'exists:promotion_sold_items,id'],
        ]);

        $soldItems = \App\Models\PromotionSoldItem::whereIn('id', $validated['sold_item_ids'])->get();

        \Illuminate\Support\Facades\DB::transaction(function () use ($soldItems, $validated, $request) {
            foreach ($soldItems as $soldItem) {
                // Record cancel log
                \App\Models\PromotionSoldCancelLog::create([
                    'promotion_item_id' => $soldItem->promotion_item_id,
                    'user_id' => $request->user()?->id,
                    'reference_number' => $soldItem->reference_number,
                    'promotion_price' => $soldItem->promotion_price,
                    'reason' => $validated['reason'],
                ]);

                // Delete sold item record so item reverts to unsold
                $soldItem->delete();
            }
        });

        return back()->with('success', 'Successfully canceled ' . count($soldItems) . ' sold item(s). Items are now active.');
    }

    /**
     * Get the latest daily gold price details for real-time polling.
     */
    public function getLatestPrice(): \Illuminate\Http\JsonResponse
    {
        $latestDailyPrice = \App\Models\DailyPriceHistory::latest('id')->first();
        $latestGoldPrice = $latestDailyPrice ? (float) $latestDailyPrice->gold_price : 0;
        $latestPriceUpdatedAt = $latestDailyPrice?->updated_at ?? $latestDailyPrice?->created_at;

        return response()->json([
            'latest_gold_price' => $latestGoldPrice,
            'latest_price_history_id' => $latestDailyPrice?->id,
            'latest_price_updated_at' => $latestPriceUpdatedAt ? $latestPriceUpdatedAt->toIso8601String() : null,
        ]);
    }
}
