<?php

namespace Modules\Admin\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Ví thành viên và vòng đời phí tin đăng.
 *
 * - Khi thành viên đăng tin: trừ phí ngay (giữ tiền), tin ở trạng thái "pending", fee_settled = false.
 * - Admin duyệt: tin hiển thị, tính ngày hết hạn từ lúc duyệt, fee_settled = true.
 * - Admin từ chối hoặc xóa tin chưa duyệt: hoàn phí vào ví.
 * Mọi thay đổi số dư đều khóa dòng user (lockForUpdate) và ghi vào wallet_transactions.
 */
class MarketplaceService
{
    public function credit(int $userId, int $amount, string $type, string $description, array $refs = []): int
    {
        return $this->move($userId, $amount, $type, $description, $refs);
    }

    public function debit(int $userId, int $amount, string $type, string $description, array $refs = []): int
    {
        return $this->move($userId, -$amount, $type, $description, $refs);
    }

    private function move(int $userId, int $amount, string $type, string $description, array $refs): int
    {
        return DB::transaction(function () use ($userId, $amount, $type, $description, $refs): int {
            $user = DB::table('users')->where('id', $userId)->lockForUpdate()->first(['id', 'balance']);
            abort_unless($user, 404);
            $balance = (int) $user->balance + $amount;
            if ($balance < 0) {
                throw ValidationException::withMessages([
                    'balance' => 'Số dư tài khoản không đủ. Cần thêm '.cms_money(-$balance).' – vui lòng nạp tiền.',
                ]);
            }
            DB::table('users')->where('id', $userId)->update(['balance' => $balance, 'updated_at' => now()]);
            DB::table('wallet_transactions')->insert([
                'user_id' => $userId, 'type' => $type, 'amount' => $amount, 'balance_after' => $balance,
                'description' => mb_substr($description, 0, 255),
                'product_id' => $refs['product_id'] ?? null, 'topup_id' => $refs['topup_id'] ?? null,
                'created_by' => $refs['created_by'] ?? auth()->id(),
                'created_at' => now(), 'updated_at' => now(),
            ]);

            return $balance;
        });
    }

    public function packages()
    {
        return DB::table('listing_packages')->where('active', true)->orderBy('position')->orderBy('id')->get();
    }

    public function fee(object $package, int $days): int
    {
        return (int) $package->price_per_day * $days;
    }

    /** Duyệt tin: hiển thị và tính hạn đăng từ thời điểm duyệt. */
    public function approve(int $productId): void
    {
        DB::transaction(function () use ($productId): void {
            $listing = DB::table('products')->where('id', $productId)->lockForUpdate()->first();
            abort_unless($listing, 404);
            $fields = ['status' => 'approved', 'reject_reason' => null, 'fee_settled' => true, 'updated_at' => now()];
            if ($listing->status !== 'approved') {
                $fields['active'] = true;
                $fields['published_at'] = now()->toDateTimeString();
                // Tin mới/đăng lại (phí đang giữ) tính hạn từ lúc duyệt; tin sửa nội dung giữ nguyên hạn cũ.
                $fields['expires_at'] = ($listing->fee_settled && $listing->expires_at) || ! $listing->days
                    ? $listing->expires_at : now()->addDays($listing->days)->toDateTimeString();
            }
            DB::table('products')->where('id', $productId)->update($fields);
        });
    }

    /** Từ chối tin: hoàn phí đang giữ (nếu có). */
    public function reject(int $productId, string $reason): void
    {
        DB::transaction(function () use ($productId, $reason): void {
            $listing = DB::table('products')->where('id', $productId)->lockForUpdate()->first();
            abort_unless($listing, 404);
            $this->refundHeldFee($listing, 'Hoàn phí tin #'.$listing->id.' bị từ chối');
            DB::table('products')->where('id', $productId)->update([
                'status' => 'rejected', 'reject_reason' => $reason, 'active' => false,
                'fee_settled' => true, 'updated_at' => now(),
            ]);
        });
    }

    public function refundHeldFee(object $listing, string $description): void
    {
        if ($listing->user_id && ! $listing->fee_settled && $listing->fee > 0) {
            $this->credit((int) $listing->user_id, (int) $listing->fee, 'refund', $description, ['product_id' => $listing->id]);
            DB::table('products')->where('id', $listing->id)->update(['fee_settled' => true]);
        }
    }

    public function approveTopup(int $id, ?string $note): void
    {
        DB::transaction(function () use ($id, $note): void {
            $topup = DB::table('topup_requests')->where('id', $id)->lockForUpdate()->first();
            abort_unless($topup, 404);
            if ($topup->status !== 'pending') {
                throw ValidationException::withMessages(['topup' => 'Yêu cầu này đã được xử lý trước đó.']);
            }
            $this->credit((int) $topup->user_id, (int) $topup->amount, 'topup', 'Nạp tiền theo yêu cầu #'.$topup->id, ['topup_id' => $topup->id]);
            DB::table('topup_requests')->where('id', $id)->update([
                'status' => 'approved', 'admin_note' => $note, 'processed_by' => auth()->id(),
                'processed_at' => now(), 'updated_at' => now(),
            ]);
        });
    }

    public function rejectTopup(int $id, string $note): void
    {
        DB::transaction(function () use ($id, $note): void {
            $topup = DB::table('topup_requests')->where('id', $id)->lockForUpdate()->first();
            abort_unless($topup, 404);
            if ($topup->status !== 'pending') {
                throw ValidationException::withMessages(['topup' => 'Yêu cầu này đã được xử lý trước đó.']);
            }
            DB::table('topup_requests')->where('id', $id)->update([
                'status' => 'rejected', 'admin_note' => $note, 'processed_by' => auth()->id(),
                'processed_at' => now(), 'updated_at' => now(),
            ]);
        });
    }
}
