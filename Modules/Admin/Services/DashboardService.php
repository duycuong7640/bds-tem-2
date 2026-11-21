<?php

namespace Modules\Admin\Services;

use Modules\Admin\Repositories\CmsRepository;

class DashboardService
{
    public function __construct(private CmsRepository $repository) {}

    public function summary(): array
    {
        $stats = [];
        foreach (['products', 'posts', 'contacts', 'subscribers'] as $key) {
            if (admin_can($key.'.view')) {
                $stats[$key] = $this->repository->table($key)->count();
            }
        }

        $recent = admin_can('contacts.view') ? $this->repository->table('contacts')->latest('id')->limit(5)->get() : collect();
        $days = collect(range(6, 0))->map(fn ($daysAgo) => today()->subDays($daysAgo));
        $traffic = [];
        if (isset($stats['contacts']) || isset($stats['subscribers'])) {
            $traffic = $days->map(function ($day) use ($stats) {
                $counts = ['day' => $day->format('d/m')];
                foreach (['contacts', 'subscribers'] as $key) {
                    $counts[$key] = isset($stats[$key]) ? $this->repository->table($key)
                        ->where('created_at', '>=', $day)->where('created_at', '<', $day->copy()->addDay())->count() : 0;
                }

                return $counts;
            })->all();
        }

        $marketplace = [];
        if (admin_can('products.view')) {
            $marketplace[] = ['Tin chờ duyệt', $this->repository->table('products')->where('status', 'pending')->count(), 'hourglass-half', route('admin.index', ['products', 'status' => 'pending'])];
        }
        if (admin_can('topups.view')) {
            $marketplace[] = ['Nạp tiền chờ duyệt', \DB::table('topup_requests')->where('status', 'pending')->count(), 'wallet', route('admin.topups', ['status' => 'pending'])];
            $marketplace[] = ['Doanh thu phí đăng tin', cms_money((int) -\DB::table('wallet_transactions')->where('type', 'listing_fee')->sum('amount') - (int) \DB::table('wallet_transactions')->where('type', 'refund')->sum('amount')), 'coins', route('admin.transactions')];
        }
        if (admin_can('members.view')) {
            $marketplace[] = ['Thành viên', \DB::table('users')->where('role', config('cms.member_role'))->count(), 'user-friends', route('admin.members')];
        }

        return compact('stats', 'recent', 'traffic', 'marketplace');
    }
}
