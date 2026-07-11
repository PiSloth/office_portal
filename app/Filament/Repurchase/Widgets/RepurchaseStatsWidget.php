<?php

namespace App\Filament\Repurchase\Widgets;

use App\Modules\Purchase\Models\PurchaseRequest;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RepurchaseStatsWidget extends StatsOverviewWidget
{
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $announcements = \App\Models\AnnouncementGoldPrice::get();
        $lateCount = 0;
        $vouchersCount = 0;

        foreach ($announcements as $announcement) {
            $updateTime = \App\Models\DailyPriceHistory::where('created_at', '>=', $announcement->announcement_datetime)
                ->orderBy('created_at', 'asc')
                ->first()?->created_at;

            if ($updateTime) {
                if ($announcement->announcement_datetime->diffInMinutes($updateTime) > 10) {
                    $lateCount++;
                }
            } else {
                if ($announcement->announcement_datetime->diffInMinutes(now()) > 10) {
                    $lateCount++;
                }
            }

            $query = PurchaseRequest::query()
                ->where('created_at', '>=', $announcement->announcement_datetime);

            if ($updateTime) {
                $query->where('created_at', '<', $updateTime);
            }

            $vouchersCount += $query->count();
        }

        return [
            Stat::make('Total Purchase Requests', PurchaseRequest::count())
                ->icon('heroicon-o-shopping-bag')
                ->color('primary'),
            Stat::make('Total Purchase Amount (Today)', 'MMK ' . number_format(PurchaseRequest::whereDate('created_at', today())->sum('total_amount'), 2))
                ->icon('heroicon-o-currency-dollar')
                ->color('success'),
            Stat::make('Draft / Pending Requests', PurchaseRequest::whereHas('workflowState', function ($query) {
                $query->where('is_start', true)->orWhere('is_end', false);
            })->count())
                ->icon('heroicon-o-clock')
                ->color('warning'),
            Stat::make('Late Gold Price Updates', $lateCount)
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->description('Official gold announcements not updated in system within 10 mins'),
            Stat::make('Vouchers (Old Gold Price)', $vouchersCount)
                ->icon('heroicon-o-document-duplicate')
                ->color('danger')
                ->description('Invoiced during latency window with outdated price'),
        ];
    }
}
