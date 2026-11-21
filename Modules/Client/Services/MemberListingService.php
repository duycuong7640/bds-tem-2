<?php

namespace Modules\Client\Services;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Admin\Services\MarketplaceService;

/**
 * Thành viên tự đăng tin: kiểm tra dữ liệu, tải ảnh, trừ phí từ ví và gửi duyệt.
 */
class MemberListingService
{
    public const MAX_IMAGES = 10;

    public function __construct(private MarketplaceService $marketplace, private WebsiteService $website) {}

    public function owned(int $userId, int $id): object
    {
        $listing = DB::table('products')->where('id', $id)->where('user_id', $userId)->first();
        abort_unless($listing, 404);

        return $listing;
    }

    /** Tin cần thanh toán lại khi tạo mới, bị từ chối (đã hoàn phí) hoặc đã hết hạn. */
    public function needsPayment(?object $listing): bool
    {
        return $listing === null || $listing->status === 'rejected'
            || ($listing->expires_at && $listing->expires_at <= now()->toDateTimeString());
    }

    public function save(Request $request, object $user, ?object $listing = null): object
    {
        $paying = $this->needsPayment($listing);
        $existing = $listing ? (json_decode($listing->gallery ?? '[]', true) ?: []) : [];
        $data = $this->validate($request, $paying, $existing);
        $kept = array_values(array_diff($existing, $data['remove_images'] ?? []));
        $total = count($kept) + count($request->file('images', []));
        if ($total < 1 || $total > self::MAX_IMAGES) {
            throw ValidationException::withMessages(['images' => $total < 1
                ? 'Vui lòng tải lên ít nhất 1 ảnh bất động sản.'
                : 'Mỗi tin tối đa '.self::MAX_IMAGES.' ảnh. Hãy bỏ bớt ảnh cũ hoặc ảnh mới.']);
        }

        $category = $this->website->categories()->firstWhere('id', (int) $data['category_id']);
        $package = $paying ? DB::table('listing_packages')->where('id', $data['package_id'])->first() : null;
        $fee = $package ? $this->marketplace->fee($package, (int) $data['days']) : 0;
        if ($fee > (int) DB::table('users')->where('id', $user->id)->value('balance')) {
            throw ValidationException::withMessages([
                'package_id' => 'Số dư không đủ để thanh toán '.cms_money($fee).'. Vui lòng nạp thêm tiền hoặc chọn gói/số ngày khác.',
            ]);
        }

        $uploaded = [];
        try {
            foreach ($request->file('images', []) as $file) {
                $uploaded[] = '/storage/'.$file->store('media/members/'.$user->id, 'public');
            }
            $gallery = array_slice([...$kept, ...$uploaded], 0, self::MAX_IMAGES);
            $autoApprove = (string) site_setting('auto_approve', '0') === '1';

            return DB::transaction(function () use ($data, $user, $listing, $paying, $package, $fee, $gallery, $category, $autoApprove) {
                $text = trim(preg_replace("/\r\n?/", "\n", $data['body']));
                $fields = [
                    'name' => $data['name'], 'menu_id' => $category->id, 'listing_type' => $category->type,
                    'description' => '<p>'.e(Str::limit(preg_replace('/\s+/u', ' ', $text), 220)).'</p>',
                    'body' => '<p>'.implode('</p><p>', array_map(fn ($line) => nl2br(e($line), false), preg_split("/\n{2,}/", $text))).'</p>',
                    'price' => $this->price($data), 'area' => $data['area'],
                    'province' => $data['province'], 'district' => $data['district'], 'address' => $data['address'],
                    'bedrooms' => $data['bedrooms'] ?? null, 'toilets' => $data['toilets'] ?? null, 'floors' => $data['floors'] ?? null,
                    'direction' => $data['direction'] ?? null, 'legal' => $data['legal'] ?? null,
                    'contact_name' => $data['contact_name'], 'contact_phone' => $data['contact_phone'], 'contact_email' => $data['contact_email'] ?? null,
                    'image' => $gallery[0], 'gallery' => json_encode($gallery, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'status' => $autoApprove ? 'approved' : 'pending', 'reject_reason' => null, 'active' => true,
                    'updated_at' => now(),
                ];
                if ($paying) {
                    $fields += [
                        'package_id' => $package->id, 'priority' => $package->priority, 'days' => (int) $data['days'],
                        'fee' => $fee, 'fee_settled' => $autoApprove,
                        'published_at' => now()->toDateTimeString(),
                        'expires_at' => $autoApprove ? now()->addDays((int) $data['days'])->toDateTimeString() : null,
                    ];
                } elseif ($autoApprove) {
                    $fields['published_at'] = $listing->published_at ?? now()->toDateTimeString();
                }

                if ($listing) {
                    DB::table('products')->where('id', $listing->id)->update($fields);
                    $id = $listing->id;
                } else {
                    $id = DB::table('products')->insertGetId($fields + [
                        'slug' => $this->slug($data['name']), 'user_id' => $user->id, 'created_at' => now(),
                    ]);
                }
                if ($fee > 0) {
                    $this->marketplace->debit($user->id, $fee, 'listing_fee',
                        'Phí đăng tin #'.$id.' – '.$package->name.' × '.$data['days'].' ngày', ['product_id' => $id, 'created_by' => $user->id]);
                }

                return DB::table('products')->find($id);
            });
        } catch (\Throwable $exception) {
            foreach ($uploaded as $url) {
                Storage::disk('public')->delete(Str::after($url, '/storage/'));
            }
            throw $exception;
        }
    }

    public function delete(object $user, int $id): void
    {
        DB::transaction(function () use ($user, $id): void {
            $listing = DB::table('products')->where('id', $id)->where('user_id', $user->id)->lockForUpdate()->first();
            abort_unless($listing, 404);
            $this->marketplace->refundHeldFee($listing, 'Hoàn phí tin #'.$id.' do thành viên xóa trước khi duyệt');
            DB::table('products')->where('id', $id)->delete();
            foreach (json_decode($listing->gallery ?? '[]', true) ?: [] as $url) {
                if (str_starts_with($url, '/storage/media/members/'.$user->id.'/')) {
                    Storage::disk('public')->delete(Str::after($url, '/storage/'));
                }
            }
        });
    }

    private function price(array $data): int
    {
        return match ($data['price_unit']) {
            'ty' => (int) round($data['price_value'] * 1_000_000_000),
            'trieu' => (int) round($data['price_value'] * 1_000_000),
            default => 0,
        };
    }

    private function slug(string $name): string
    {
        $base = rtrim(substr(Str::slug($name) ?: 'tin-dang', 0, 170), '-');
        $slug = $base;
        for ($i = 2; DB::table('products')->where('slug', $slug)->exists(); $i++) {
            $slug = $base.'-'.$i;
        }

        return $slug;
    }

    private function validate(Request $request, bool $paying, array $existing): array
    {
        $categories = $this->website->categories()->pluck('id')->all();
        $minDays = fn () => (int) DB::table('listing_packages')->where('id', $request->integer('package_id'))->value('min_days');

        return $request->validate([
            'category_id' => ['required', 'integer', Rule::in($categories)],
            'name' => 'required|string|min:15|max:180',
            'body' => 'required|string|min:50|max:5000',
            'province' => ['required', Rule::in(config('cms.provinces'))],
            'district' => 'required|string|max:80',
            'address' => 'required|string|max:255',
            'price_unit' => 'required|in:trieu,ty,thoa-thuan',
            'price_value' => 'nullable|required_unless:price_unit,thoa-thuan|numeric|min:0.01|max:999999',
            'area' => 'required|numeric|min:1|max:99999999',
            'bedrooms' => 'nullable|integer|min:0|max:50',
            'toilets' => 'nullable|integer|min:0|max:50',
            'floors' => 'nullable|integer|min:0|max:100',
            'direction' => ['nullable', Rule::in(config('cms.directions'))],
            'legal' => ['nullable', Rule::in(config('cms.legal_options'))],
            'images' => ['nullable', 'array', 'max:'.self::MAX_IMAGES],
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
            'remove_images' => 'nullable|array',
            'remove_images.*' => ['string', Rule::in($existing)],
            'contact_name' => 'required|string|max:120',
            'contact_phone' => ['required', 'string', 'regex:/^(0|\+84)[0-9]{9,10}$/'],
            'contact_email' => 'nullable|email:rfc|max:254',
            'package_id' => $paying ? ['required', 'integer', Rule::exists('listing_packages', 'id')->where('active', true)] : ['exclude'],
            'days' => $paying ? ['required', 'integer', Rule::in(config('cms.listing_days')), function (string $field, mixed $value, Closure $fail) use ($minDays): void {
                if ((int) $value < $minDays()) {
                    $fail('Gói đã chọn yêu cầu đăng tối thiểu '.$minDays().' ngày.');
                }
            }] : ['exclude'],
            'agree' => $paying ? 'accepted' : 'exclude',
        ], [
            'category_id.required' => 'Vui lòng chọn loại bất động sản.', 'category_id.in' => 'Loại bất động sản không hợp lệ.',
            'name.required' => 'Vui lòng nhập tiêu đề tin.', 'name.min' => 'Tiêu đề tối thiểu 15 ký tự để người xem dễ hiểu.',
            'body.required' => 'Vui lòng nhập mô tả chi tiết.', 'body.min' => 'Mô tả tối thiểu 50 ký tự.',
            'province.required' => 'Vui lòng chọn tỉnh/thành phố.', 'district.required' => 'Vui lòng nhập quận/huyện.',
            'address.required' => 'Vui lòng nhập địa chỉ.',
            'price_value.required_unless' => 'Vui lòng nhập giá hoặc chọn “Thỏa thuận”.', 'price_value.numeric' => 'Giá phải là số, ví dụ 3.5',
            'price_value.min' => 'Giá phải lớn hơn 0.',
            'area.required' => 'Vui lòng nhập diện tích.', 'area.numeric' => 'Diện tích phải là số.', 'area.min' => 'Diện tích phải lớn hơn 0.',
            'images.max' => 'Mỗi tin tối đa '.self::MAX_IMAGES.' ảnh.',
            'images.*.image' => 'Tệp tải lên phải là hình ảnh.', 'images.*.mimes' => 'Ảnh phải có định dạng JPG, PNG hoặc WEBP.',
            'images.*.max' => 'Mỗi ảnh tối đa 5MB.',
            'contact_name.required' => 'Vui lòng nhập tên liên hệ.', 'contact_phone.required' => 'Vui lòng nhập số điện thoại liên hệ.',
            'contact_phone.regex' => 'Số điện thoại phải bắt đầu bằng 0 hoặc +84 và gồm 10–11 chữ số.',
            'contact_email.email' => 'Email liên hệ không hợp lệ.',
            'package_id.required' => 'Vui lòng chọn gói tin.', 'package_id.exists' => 'Gói tin không còn được bán.',
            'days.required' => 'Vui lòng chọn số ngày đăng.', 'days.in' => 'Số ngày đăng không hợp lệ.',
            'agree.accepted' => 'Bạn cần đồng ý với quy định đăng tin.',
            'integer' => ':attribute phải là số nguyên.', 'min' => ':attribute không hợp lệ.',
        ], [
            'bedrooms' => 'Số phòng ngủ', 'toilets' => 'Số toilet', 'floors' => 'Số tầng',
        ]);
    }
}
