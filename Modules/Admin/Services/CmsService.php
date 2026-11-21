<?php

namespace Modules\Admin\Services;

use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Admin\Repositories\CmsRepository;

class CmsService
{
    public function __construct(private CmsRepository $repository, private MarketplaceService $marketplace) {}

    public function records(string $resource, string $query, array $filters = [])
    {
        $column = $resource === 'subscribers' ? 'email' : 'name';

        return $this->repository->table($resource)
            ->when($query !== '', fn ($builder) => $builder->where(function ($search) use ($column, $query, $resource): void {
                $search->where($column, 'like', '%'.$query.'%');
                if ($resource === 'products') {
                    $search->orWhere('contact_phone', 'like', '%'.$query.'%')->orWhere('id', (int) $query);
                }
            }))
            // Thành viên (member) được quản lý ở mục riêng, không lẫn vào tài khoản quản trị.
            ->when($resource === 'users', fn ($builder) => $builder->whereIn('role', array_keys(config('cms.roles'))))
            ->when($resource === 'products' && isset($filters['status']), fn ($builder) => $builder->where('status', $filters['status']))
            ->when($resource === 'products' && isset($filters['listing_type']), fn ($builder) => $builder->where('listing_type', $filters['listing_type']))
            ->when($resource === 'packages', fn ($builder) => $builder->reorder()->orderBy('position'))
            ->latest('id')->paginate(12)->withQueryString();
    }

    public function menuChoices(?int $id): array
    {
        $choices = $this->menuTree();
        $excluded = $id === null ? [] : $this->descendants($id);

        return array_filter($choices, fn ($key) => ! in_array($key, $excluded, true), ARRAY_FILTER_USE_KEY);
    }

    public function menuTree(): array
    {
        $groups = $this->repository->table('menus')->orderBy('position')->orderBy('id')->get()->groupBy('parent_id');
        $collect = function ($parent, int $depth) use (&$collect, $groups): array {
            $choices = [];
            foreach ($groups->get($parent, []) as $menu) {
                $menu->depth = $depth;
                $choices[$menu->id] = $menu;
                $choices += $collect($menu->id, $depth + 1);
            }

            return $choices;
        };

        return $collect('', 0);
    }

    public function menuCategories(string $resource): array
    {
        return array_filter($this->menuTree(), fn ($menu) => $menu->content_type === $resource);
    }

    private function menuHasContent(int $id): bool
    {
        return $this->repository->table('posts')->where('menu_id', $id)->exists()
            || $this->repository->table('products')->where('menu_id', $id)->exists();
    }

    private function descendants(int $id): array
    {
        $tree = $this->menuTree();
        $excluded = [$id];
        foreach ($tree as $menu) {
            if (in_array($menu->parent_id, $excluded, true)) {
                $excluded[] = $menu->id;
            }
        }

        return $excluded;
    }

    public function save(Request $request, string $resource, ?int $id): void
    {
        DB::transaction(function () use ($request, $resource, $id): void {
            if (in_array($resource, ['users', 'menus'], true)) {
                $this->repository->table($resource)->orderBy('id')->lockForUpdate()->get(['id']);
            }
            abort_if($id === null && in_array($resource, ['contacts', 'subscribers'], true), 405);
            $record = $id === null ? null : $this->repository->find($resource, $id);
            if (in_array($resource, ['posts', 'products'], true) && $request->filled('menu_id')) {
                $this->repository->table('menus')->where('id', $request->integer('menu_id'))->lockForUpdate()->first();
            }
            $fields = $this->validated($request, $resource, $id);
            if ($resource === 'menus' && $record && array_key_exists('content_type', $fields)
                && $fields['content_type'] !== $record->content_type && $this->menuHasContent($id)) {
                throw ValidationException::withMessages(['content_type' => 'Hãy chuyển nội dung sang danh mục khác trước khi đổi loại menu.']);
            }
            if ($resource === 'users') {
                if ($record?->id === $request->user()->id && ($fields['role'] !== 'admin' || ! $fields['active'])) {
                    throw ValidationException::withMessages(['role' => 'Tài khoản đang đăng nhập phải giữ quyền Admin và trạng thái hoạt động.']);
                }
                if ($record && $record->role === 'admin' && ($fields['role'] !== 'admin' || ! $fields['active'])) {
                    $this->ensureOtherAdmin($record->id);
                }
                if ($record && (! empty($fields['password']) || $fields['role'] !== $record->role
                    || (bool) $fields['active'] !== (bool) $record->active)) {
                    $fields['remember_token'] = Str::random(60);
                    $this->repository->revokeSessions($record->id);
                }
                if (! empty($fields['password'])) {
                    $fields['password'] = Hash::make($fields['password']);
                } else {
                    unset($fields['password']);
                }
            }
            if (in_array($resource, ['posts', 'products'], true) && array_key_exists('publication_time', $fields)) {
                $time = $fields['publication_time'];
                if ($time === 'now') {
                    $existing = $record?->published_at;
                    $fields['published_at'] = $record?->active && $existing && $existing <= now()->toDateTimeString()
                        ? $existing
                        : ((bool) $fields['active'] ? now()->toDateTimeString() : null);
                } elseif ($time === 'custom') {
                    $chosen = str_replace('T', ' ', $fields['publication_at']).':00';
                    $existing = $record?->published_at;
                    $fields['published_at'] = $existing && substr($existing, 0, 16) === substr($chosen, 0, 16) ? $existing : $chosen;
                } elseif ($time === 'keep') {
                    if (! $record?->published_at) {
                        throw ValidationException::withMessages(['publication_time' => 'Nội dung này chưa có lịch xuất bản.']);
                    }
                    unset($fields['published_at']);
                } else {
                    $fields['published_at'] .= ' '.$time.':00';
                }
                unset($fields['publication_time']);
            }
            unset($fields['publication_at']);
            if ($resource === 'products') {
                $fields = $this->listingFields($fields, $record);
            }
            if ($record === null && in_array($resource, ['products', 'posts'], true) && ! isset($fields['slug'])) {
                $this->createWithSlug($resource, $fields);

                return;
            }
            if (array_key_exists('slug', $fields) && $fields['slug'] === null) {
                unset($fields['slug']);
            }
            try {
                $this->repository->save($resource, $id, $fields);
            } catch (UniqueConstraintViolationException $exception) {
                if (isset($fields['slug']) && $this->isSlugConflict($exception, $resource)) {
                    throw ValidationException::withMessages(['slug' => 'Slug này đã được sử dụng. Vui lòng chọn slug khác.']);
                }
                throw $exception;
            }
        }, 3);
    }

    public function delete(Request $request, string $resource, int $id): void
    {
        DB::transaction(function () use ($request, $resource, $id): void {
            if (in_array($resource, ['users', 'menus'], true)) {
                $this->repository->table($resource)->orderBy('id')->lockForUpdate()->get(['id']);
            }
            $record = $this->repository->find($resource, $id);
            if ($resource === 'users' && $id === $request->user()->id) {
                throw ValidationException::withMessages(['user' => 'Không thể xóa tài khoản đang đăng nhập.']);
            }
            if ($resource === 'users' && $record->role === 'admin') {
                $this->ensureOtherAdmin($id);
            }
            if ($resource === 'menus' && $this->repository->table('menus')->where('parent_id', $id)->exists()) {
                throw ValidationException::withMessages(['menu' => 'Hãy chuyển hoặc xóa các menu con trước.']);
            }
            if ($resource === 'menus' && $this->menuHasContent($id)) {
                throw ValidationException::withMessages(['menu' => 'Hãy chuyển nội dung sang danh mục khác trước khi xóa menu.']);
            }
            if ($resource === 'users') {
                $this->repository->revokeSessions($id);
            }
            if ($resource === 'products') {
                $this->marketplace->refundHeldFee($record, 'Hoàn phí tin #'.$id.' bị xóa trước khi duyệt');
            }
            if ($resource === 'packages' && $this->repository->table('products')->where('package_id', $id)->exists()) {
                throw ValidationException::withMessages(['package' => 'Gói đang được dùng cho tin đăng. Hãy tắt "Đang bán" thay vì xóa.']);
            }
            $this->repository->delete($resource, $id);
        });
    }

    /** Chuẩn hóa dữ liệu tin đăng khi admin lưu: album ảnh, ưu tiên theo gói, duyệt/từ chối và hoàn phí. */
    private function listingFields(array $fields, ?object $record): array
    {
        $fields += ['package_id' => null, 'days' => null, 'expires_at' => null, 'reject_reason' => null];
        $gallery = preg_split('/\R/', (string) ($fields['gallery'] ?? ''), -1, PREG_SPLIT_NO_EMPTY);
        $fields['gallery'] = json_encode(array_values(array_unique(array_map('trim', $gallery))), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $fields['priority'] = $fields['package_id']
            ? (int) $this->repository->table('packages')->where('id', $fields['package_id'])->value('priority') : 0;
        $fields['expires_at'] = empty($fields['expires_at']) ? null : str_replace('T', ' ', $fields['expires_at']).':00';
        if ($fields['status'] !== 'rejected') {
            $fields['reject_reason'] = null;
        }
        if ($fields['status'] === 'rejected' && $record && $record->status !== 'rejected') {
            $this->marketplace->refundHeldFee($record, 'Hoàn phí tin #'.$record->id.' bị từ chối');
            $fields['fee_settled'] = true;
            $fields['active'] = false;
        }
        if ($fields['status'] === 'approved' && (! $record || $record->status !== 'approved')) {
            $fields['fee_settled'] = true;
            if ($fields['expires_at'] === null && ! empty($fields['days'])) {
                $fields['expires_at'] = now()->addDays((int) $fields['days'])->toDateTimeString();
            }
        }

        return $fields;
    }

    private function createWithSlug(string $resource, array $fields): void
    {
        for ($attempt = 1; ; $attempt++) {
            $fields['slug'] = $this->availableSlug($resource, $fields['name']);
            try {
                DB::transaction(fn () => $this->repository->save($resource, null, $fields));

                return;
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt >= 3 || ! $this->isSlugConflict($exception, $resource)) {
                    throw $exception;
                }
            }
        }
    }

    private function isSlugConflict(UniqueConstraintViolationException $exception, string $resource): bool
    {
        $detail = $exception->errorInfo[2] ?? '';

        return str_contains($detail, $resource.'.slug') || str_contains($detail, $resource.'_slug_unique');
    }

    private function availableSlug(string $resource, string $name): string
    {
        $base = Str::slug($name) ?: ($resource === 'products' ? 'tin-dang' : 'bai-viet');
        $slug = rtrim(substr($base, 0, 180), '-');
        for ($suffix = 2; $this->repository->table($resource)->where('slug', $slug)->exists(); $suffix++) {
            $ending = '-'.$suffix;
            $slug = rtrim(substr($base, 0, 180 - strlen($ending)), '-').$ending;
        }

        return $slug;
    }

    private function ensureOtherAdmin(int $id): void
    {
        $anotherAdmin = $this->repository->table('users')->where('role', 'admin')
            ->where('active', true)->where('id', '!=', $id)->exists();
        if (! $anotherAdmin) {
            throw ValidationException::withMessages(['role' => 'Cần giữ ít nhất một tài khoản Admin hoạt động.']);
        }
    }

    public static function safeLink(): Closure
    {
        return function (string $field, string $value, Closure $fail): void {
            if (preg_match('~^/(?!/)[^\s\\\\]*$~D', $value) || (
                preg_match('~^https?://~i', $value) && ! str_contains($value, '\\') && filter_var($value, FILTER_VALIDATE_URL)
            )) {
                return;
            }
            $fail('Dùng đường dẫn bắt đầu bằng / hoặc URL http(s) hợp lệ.');
        };
    }

    public static function imageRules(bool $required = false): array
    {
        return [$required ? 'required' : 'nullable', 'string', 'max:500', self::safeLink()];
    }

    private function validated(Request $request, string $resource, ?int $id): array
    {
        $name = 'required|string|max:180';
        $active = 'required|boolean';
        $link = ['nullable', 'string', 'max:500', self::safeLink()];
        $position = 'required|integer|min:0|max:2147483647';
        if (is_string($request->input('email'))) {
            $request->merge(['email' => Str::lower($request->input('email'))]);
        }
        if (in_array($resource, ['posts', 'products'], true)) {
            foreach (['description' => 2000, 'body' => 200000] as $field => $limit) {
                $html = $request->input($field);
                if (! is_string($html) || mb_strlen($html) > $limit) {
                    continue;
                }
                $clean = $this->cleanHtml($html);
                $text = html_entity_decode(strip_tags($clean), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $hasText = preg_match('/[^\s\p{Z}\p{Cf}]/u', $text) === 1;
                $hasImage = $field === 'body' && preg_match('/<img\b[^>]*\ssrc="/i', $clean) === 1;
                $request->merge([$field => $hasText || $hasImage ? $clean : null]);
            }
        }
        $unique = fn (string $column) => Rule::unique(config("cms.resources.$resource.table"), $column)->ignore($id);
        $content = [
            'name' => $name,
            'slug' => ['nullable', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $unique('slug')],
            'menu_id' => ['required', 'integer', Rule::exists('menus', 'id')->where('content_type', $resource)],
            'body' => 'required|string|max:200000', 'image' => self::imageRules(true), 'active' => $active,
            'published_at' => 'nullable|required_if:publication_time,09:00,15:00,19:00|date_format:Y-m-d',
            'publication_time' => ['sometimes', 'required', Rule::in(['now', 'keep', 'custom', ...config('cms.post_publication_times')])],
            'publication_at' => 'nullable|required_if:publication_time,custom|date_format:Y-m-d\TH:i',
            'description' => 'required|string|max:2000',
            'seo_title' => 'nullable|string|max:70',
            'seo_description' => 'nullable|string|max:170',
        ];
        $rules = match ($resource) {
            'menus' => [
                'name' => $name, 'url' => $link, 'position' => $position, 'active' => $active,
                'content_type' => 'nullable|in:posts,products',
                'seo_title' => 'nullable|string|max:70',
                'seo_description' => 'nullable|string|max:170',
                'parent_id' => [
                    'nullable', 'integer', Rule::exists('menus', 'id'),
                    function (string $field, mixed $value, Closure $fail) use ($id): void {
                        if ($id !== null && in_array((int) $value, $this->descendants($id), true)) {
                            $fail('Không thể chọn chính menu này hoặc một menu con làm cha.');
                        }
                    },
                ],
            ],
            'posts' => $content + [
                'kind' => ['sometimes', 'required', Rule::in(array_keys(config('cms.post_categories')))],

            ],
            'products' => $content + [
                'sku' => ['nullable', 'string', 'max:80', $unique('sku')], 'price' => 'required|integer|min:0|max:999999999999',
                'listing_type' => ['required', Rule::in(array_keys(config('cms.listing_types')))],
                'status' => ['required', Rule::in(array_keys(config('cms.listing_statuses')))],
                'reject_reason' => 'nullable|required_if:status,rejected|string|max:500',
                'package_id' => ['nullable', 'integer', Rule::exists('listing_packages', 'id')],
                'days' => 'nullable|integer|min:1|max:365',
                'expires_at' => 'nullable|date_format:Y-m-d\TH:i',
                'province' => ['required', Rule::in(config('cms.provinces'))],
                'district' => 'nullable|string|max:80',
                'address' => 'required|string|max:255',
                'area' => 'required|numeric|min:1|max:99999999',
                'bedrooms' => 'nullable|integer|min:0|max:50', 'toilets' => 'nullable|integer|min:0|max:50',
                'floors' => 'nullable|integer|min:0|max:100',
                'direction' => ['nullable', Rule::in(config('cms.directions'))],
                'legal' => ['nullable', Rule::in(config('cms.legal_options'))],
                'contact_name' => 'required|string|max:120',
                'contact_phone' => ['required', 'string', 'regex:/^(0|\+84)[0-9]{9,10}$/'],
                'contact_email' => 'nullable|email:rfc|max:254',
                'gallery' => ['nullable', 'string', 'max:6000', function (string $field, mixed $value, Closure $fail): void {
                    foreach (preg_split('/\R/', (string) $value, -1, PREG_SPLIT_NO_EMPTY) as $line) {
                        $line = trim($line);
                        if ($line !== '' && ! preg_match('~^(/(?!/)[^\s\\\\]*|https?://[^\s\\\\]+)$~D', $line)) {
                            $fail('Mỗi dòng của album ảnh phải là đường dẫn bắt đầu bằng / hoặc URL http(s).');

                            return;
                        }
                    }
                }],
            ],
            'packages' => [
                'name' => 'required|string|max:80', 'badge' => 'nullable|string|max:20',
                'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'price_per_day' => 'required|integer|min:0|max:100000000',
                'priority' => 'required|integer|min:0|max:1000', 'min_days' => 'required|integer|min:1|max:365',
                'description' => 'nullable|string|max:500', 'position' => $position, 'active' => $active,
            ],
            'banners' => ['name' => $name, 'placement' => 'required|in:slide,advertisement', 'url' => $link, 'image' => self::imageRules(true), 'position' => $position, 'active' => $active],
            'contacts' => ['status' => 'required|in:new,read,resolved'],
            'subscribers' => ['active' => $active],
            'users' => [
                'name' => $name, 'email' => ['required', 'email:rfc', 'max:254', $unique('email')], 'active' => $active,
                'role' => ['required', Rule::in(array_keys(config('cms.roles')))],
                'password' => [$id ? 'nullable' : 'required', 'string', 'min:12', 'max:72'],
            ],
        };

        return $request->validate($rules, [
            'name.required' => $resource === 'products' ? 'Vui lòng nhập tiêu đề tin đăng.' : ($resource === 'posts' ? 'Vui lòng nhập tiêu đề bài viết.' : 'Vui lòng nhập tên.'),
            'description.required' => 'Vui lòng nhập tóm tắt.',
            'body.required' => 'Vui lòng nhập nội dung.',
            'image.required' => 'Vui lòng chọn ảnh đại diện.',
            'menu_id.required' => 'Vui lòng chọn danh mục.',
            'slug.unique' => 'Slug này đã được sử dụng. Vui lòng chọn slug khác.',
            'slug.regex' => 'Slug chỉ dùng chữ thường không dấu, số và dấu gạch ngang giữa các từ.',
            'sku.unique' => 'Mã SKU này đã được sử dụng.',
            'email.unique' => 'Email này đã được sử dụng.',
            'menu_id.exists' => 'Vui lòng chọn danh mục đúng loại nội dung.',
            'reject_reason.required_if' => 'Vui lòng nhập lý do từ chối để thành viên biết cần sửa gì.',
            'contact_phone.regex' => 'Số điện thoại phải bắt đầu bằng 0 hoặc +84 và gồm 10–11 chữ số.',
            'color.regex' => 'Màu nhãn phải có dạng mã hex, ví dụ #e03c31.',
            'province.required' => 'Vui lòng chọn tỉnh/thành phố.',
            'province.in' => 'Tỉnh/thành phố không hợp lệ.',
            'address.required' => 'Vui lòng nhập địa chỉ.',
            'area.required' => 'Vui lòng nhập diện tích.',
            'contact_name.required' => 'Vui lòng nhập tên người liên hệ.',
            'contact_phone.required' => 'Vui lòng nhập số điện thoại liên hệ.',
            'required' => 'Vui lòng nhập :attribute.',
            'integer' => ':attribute phải là số nguyên.',
            'numeric' => ':attribute phải là số.',
            'publication_at.required_if' => 'Vui lòng nhập ngày và giờ xuất bản.',
            'publication_at.date_format' => 'Ngày hoặc giờ xuất bản không hợp lệ.',
        ], config("cms.resources.$resource.fields") + [
            'slug' => 'Slug', 'menu_id' => 'Danh mục', 'description' => 'Tóm tắt',
            'seo_title' => 'Tiêu đề SEO', 'seo_description' => 'Mô tả SEO',
            'publication_at' => 'Thời điểm xuất bản', 'publication_time' => 'Chế độ xuất bản',
        ]);
    }

    private function cleanHtml(string $html): string
    {
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('Cache.DefinitionImpl', null);
        $config->set('HTML.DefinitionID', 'cms-content');
        $config->set('HTML.DefinitionRev', 2);
        $config->set('HTML.Allowed', 'div[class|style],p[style],span[class|style],mark[class|style],br,h2[style],h3[style],h4[style],strong,em,u,s,sub,sup,blockquote,ul[class|style],ol[start|reversed|style],li[class|style],a[href],img[class|style|src|alt|width|height],figure[class|style],figcaption,pre,code[class],hr,table[style],caption,thead,tbody,tfoot,colgroup,col[style|span|width],tr,th[style|colspan|rowspan],td[style|colspan|rowspan]');
        $config->set('Attr.AllowedClasses', [
            'image', 'image-inline', 'image_resized', 'image-style-side', 'image-style-block',
            'image-style-align-left', 'image-style-align-right', 'image-style-align-center', 'table',
            'marker-yellow', 'marker-green', 'marker-pink', 'marker-blue', 'pen-red', 'pen-green',
            'language-plaintext', 'language-javascript', 'language-css', 'language-html', 'language-php',
            'language-python', 'language-sql', 'language-json', 'language-bash',
        ]);
        $config->set('CSS.AllowedProperties', [
            'font-family', 'font-size', 'color', 'background-color', 'text-align', 'vertical-align',
            'list-style-type', 'margin-left', 'margin-right', 'width', 'height', 'border',
            'border-color', 'border-style', 'border-width', 'padding', 'float',
        ]);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true, 'tel' => true]);
        if ($definition = $config->maybeGetRawHTMLDefinition()) {
            $definition->addElement('figure', 'Block', 'Flow', 'Common');
            $definition->addElement('figcaption', 'Block', 'Flow', 'Common');
            $definition->addElement('mark', 'Inline', 'Inline', 'Common');
            $definition->addAttribute('ol', 'reversed', 'Bool');
        }

        return (new \HTMLPurifier($config))->purify($html);
    }
}
