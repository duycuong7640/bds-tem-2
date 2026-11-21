<?php

use Modules\Admin\Repositories\ConfigurationRepository;
use Modules\Admin\Services\AccessService;

function site_setting(string $key, mixed $default = null): mixed
{
    return app(ConfigurationRepository::class)->settings()[$key] ?? $default;
}

function admin_can(string $permission): bool
{
    return app(AccessService::class)->allows(auth()->user(), $permission);
}

function cms_money(int $amount): string
{
    return number_format($amount, 0, ',', '.').' ₫';
}

/** Giá BĐS dạng rút gọn: 3,5 tỷ · 12 triệu/tháng · Thỏa thuận. */
function bds_price(int|string|null $price, ?string $type = 'sale'): string
{
    $price = (int) $price;
    if ($price <= 0) {
        return 'Thỏa thuận';
    }
    $short = fn (float $value) => rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',');
    $text = match (true) {
        $price >= 1_000_000_000 => $short($price / 1_000_000_000).' tỷ',
        $price >= 1_000_000 => $short($price / 1_000_000).' triệu',
        default => number_format($price, 0, ',', '.').' ₫',
    };

    return $type === 'rent' ? $text.'/tháng' : $text;
}

function bds_area(int|float|string|null $area): string
{
    $value = (float) $area;

    return $value > 0 ? rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',').' m²' : '—';
}

function is_member(?object $user = null): bool
{
    $user ??= auth()->user();

    return $user !== null && $user->role === config('cms.member_role');
}
