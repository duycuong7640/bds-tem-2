<?php

namespace Modules\Client\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Client\Repositories\WebsiteRepository;

class WebsiteService
{
    private ?Collection $categoryCache = null;

    public function __construct(private WebsiteRepository $repository) {}

    /** Danh sách tin công khai kèm nhãn gói (VIP...). */
    public function listingQuery()
    {
        return $this->repository->published('products')
            ->leftJoin('listing_packages', 'listing_packages.id', '=', 'products.package_id')
            ->select('products.*', 'listing_packages.badge as package_badge', 'listing_packages.color as package_color');
    }

    public function home(): array
    {
        $live = fn () => $this->listingQuery()->orderByDesc('products.priority')->orderByDesc('products.published_at')->orderByDesc('products.id');
        $provinceCounts = $this->repository->published('products')->selectRaw('province, count(*) as total')
            ->groupBy('province')->pluck('total', 'province');

        return [
            'featured' => $live()->where('products.priority', '>', 0)->limit(8)->get(),
            'latestSale' => $this->listingQuery()->where('listing_type', 'sale')->orderByDesc('products.published_at')->orderByDesc('products.id')->limit(8)->get(),
            'latestRent' => $this->listingQuery()->where('listing_type', 'rent')->orderByDesc('products.published_at')->orderByDesc('products.id')->limit(8)->get(),
            'posts' => $this->repository->published('posts')->orderByDesc('published_at')->orderByDesc('id')->limit(4)->get(),
            'banners' => $this->repository->published('banners')->orderBy('position')->orderBy('id')->get()->groupBy('placement'),
            'provinceCounts' => $provinceCounts,
            'totalListings' => $provinceCounts->sum(),
        ];
    }

    /** Danh mục loại BĐS (menu content_type = products) kèm hình thức bán/thuê suy ra từ menu gốc. */
    public function categories(?string $type = null): Collection
    {
        if ($this->categoryCache === null) {
            $menus = $this->repository->published('menus')->orderBy('position')->orderBy('id')->get()->keyBy('id');
            $this->categoryCache = $menus->where('content_type', 'products')->map(function ($menu) use ($menus) {
                $root = $menu;
                while ($root->parent_id && $menus->has($root->parent_id)) {
                    $root = $menus->get($root->parent_id);
                }
                $menu->type = str_contains((string) $root->url, 'cho-thue') ? 'rent' : 'sale';
                $menu->key = Str::slug($menu->name);

                return $menu;
            })->filter(fn ($menu) => $menu->parent_id !== null)->values();
        }

        return $type ? $this->categoryCache->where('type', $type)->values() : $this->categoryCache;
    }

    public function search(Request $request, ?string $forcedType = null): array
    {
        $filters = [
            'type' => $forcedType ?? (in_array($request->query('loai'), ['sale', 'rent'], true) ? $request->query('loai') : null),
            'q' => mb_substr(trim((string) $request->query('q')), 0, 120),
            'province' => in_array($request->query('tinh'), config('cms.provinces'), true) ? $request->query('tinh') : null,
            'category' => null,
            'price' => null, 'area' => null,
            'sort' => in_array($request->query('sap-xep'), ['moi-nhat', 'gia-tang', 'gia-giam', 'dien-tich'], true) ? $request->query('sap-xep') : 'noi-bat',
        ];
        $category = $this->categories($filters['type'])->firstWhere('key', (string) $request->query('danh-muc'));
        if ($category) {
            $filters['category'] = $category->key;
            $filters['type'] ??= $category->type;
        }
        $priceRanges = $filters['type'] ? config('cms.price_ranges.'.$filters['type']) : [];
        if (array_key_exists((string) $request->query('gia'), $priceRanges)) {
            $filters['price'] = $request->query('gia');
        }
        if (array_key_exists((string) $request->query('dien-tich'), config('cms.area_ranges'))) {
            $filters['area'] = $request->query('dien-tich');
        }

        $query = $this->listingQuery()
            ->when($filters['type'], fn ($builder, $type) => $builder->where('listing_type', $type))
            ->when($category, fn ($builder) => $builder->where('products.menu_id', $category->id))
            ->when($filters['province'], fn ($builder, $province) => $builder->where('province', $province))
            ->when($filters['q'] !== '', fn ($builder) => $builder->where(fn ($search) => $search
                ->where('products.name', 'like', '%'.$filters['q'].'%')
                ->orWhere('address', 'like', '%'.$filters['q'].'%')
                ->orWhere('district', 'like', '%'.$filters['q'].'%')))
            ->when($filters['price'], function ($builder, $range): void {
                [$min, $max] = array_map('intval', explode('-', $range));
                $builder->where('products.price', '>=', $min)->when($max > 0, fn ($b) => $b->where('products.price', '<', $max));
            })
            ->when($filters['area'], function ($builder, $range): void {
                [$min, $max] = array_map('intval', explode('-', $range));
                $builder->where('area', '>=', $min)->when($max > 0, fn ($b) => $b->where('area', '<', $max));
            });
        match ($filters['sort']) {
            'moi-nhat' => $query->orderByDesc('products.published_at'),
            'gia-tang' => $query->orderByRaw('products.price = 0')->orderBy('products.price'),
            'gia-giam' => $query->orderByDesc('products.price'),
            'dien-tich' => $query->orderByDesc('area'),
            default => $query->orderByDesc('products.priority')->orderByDesc('products.published_at'),
        };

        $title = match ($filters['type']) { 'sale' => 'Nhà đất bán', 'rent' => 'Nhà đất cho thuê', default => 'Tìm kiếm bất động sản' };
        if ($category) {
            $title = $category->name;
        }
        if ($filters['province']) {
            $title .= ' tại '.$filters['province'];
        }

        return [
            'listings' => $query->orderByDesc('products.id')->paginate(12)->withQueryString(),
            'filters' => $filters, 'title' => $title, 'category' => $category,
            'priceRanges' => $priceRanges,
        ];
    }

    public function listing(string $slug): array
    {
        $item = $this->listingQuery()->where('products.slug', $slug)->firstOrFail();
        DB::table('products')->where('id', $item->id)->increment('views');
        $gallery = array_values(array_unique(array_filter([$item->image, ...(json_decode($item->gallery ?? '[]', true) ?: [])])));
        $related = $this->listingQuery()->where('products.id', '!=', $item->id)->where('listing_type', $item->listing_type)
            ->where(fn ($builder) => $builder->where('province', $item->province)->orWhere('products.menu_id', $item->menu_id))
            ->orderByDesc('products.priority')->orderByDesc('products.published_at')->limit(4)->get();
        $category = $this->categories()->firstWhere('id', $item->menu_id);
        $owner = $item->user_id ? DB::table('users')->where('id', $item->user_id)->first(['id', 'name', 'created_at']) : null;
        $ownerCount = $owner ? $this->repository->published('products')->where('user_id', $owner->id)->count() : 0;

        return compact('item', 'gallery', 'related', 'category', 'owner', 'ownerCount');
    }

    public function posts(Request $request): array
    {
        $menus = $this->repository->published('menus')->where('content_type', 'posts')->whereNotNull('parent_id')->orderBy('position')->get()
            ->each(fn ($menu) => $menu->key = Str::slug($menu->name));
        $current = $menus->firstWhere('key', (string) $request->query('chuyen-muc'));
        $posts = $this->repository->published('posts')
            ->when($current, fn ($builder) => $builder->where('menu_id', $current->id))
            ->orderByDesc('published_at')->orderByDesc('id')->paginate(9)->withQueryString();

        return compact('menus', 'current', 'posts');
    }

    public function post(string $slug): array
    {
        $item = $this->repository->published('posts')->where('slug', $slug)->firstOrFail();
        $related = $this->repository->published('posts')->where('id', '!=', $item->id)->orderByDesc('published_at')->limit(4)->get();
        $listings = $this->listingQuery()->orderByDesc('products.priority')->orderByDesc('products.published_at')->limit(4)->get();

        return compact('item', 'related', 'listings');
    }

    public function packages(): Collection
    {
        return DB::table('listing_packages')->where('active', true)->orderBy('position')->orderBy('id')->get();
    }

    /** Tra cứu trạng thái tin bằng mã tin + số điện thoại liên hệ. */
    public function lookup(Request $request): object
    {
        $data = $request->validateWithBag('lookup', [
            'code' => ['required', 'string', 'max:20', 'regex:/^#?[0-9]{1,10}$/'],
            'phone' => ['required', 'string', 'regex:/^(0|\+84)[0-9]{9,10}$/'],
        ], [
            'code.required' => 'Vui lòng nhập mã tin.', 'code.regex' => 'Mã tin chỉ gồm chữ số, ví dụ 1024.',
            'phone.required' => 'Vui lòng nhập số điện thoại.', 'phone.regex' => 'Số điện thoại phải bắt đầu bằng 0 hoặc +84 và gồm 10–11 chữ số.',
        ]);
        $listing = DB::table('products')->leftJoin('listing_packages', 'listing_packages.id', '=', 'products.package_id')
            ->where('products.id', (int) ltrim($data['code'], '#'))->where('contact_phone', $data['phone'])
            ->first(['products.*', 'listing_packages.name as package_name']);
        if (! $listing) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'code' => 'Không tìm thấy tin đăng khớp với mã tin và số điện thoại này.',
            ])->errorBag('lookup');
        }

        return $listing;
    }

    public function menu(): array
    {
        $groups = $this->repository->published('menus')->orderBy('position')->orderBy('id')->get()->groupBy('parent_id');
        $branch = function ($parent) use (&$branch, $groups): array {
            return collect($groups->get($parent, []))->map(fn ($menu) => [
                'name' => $menu->name, 'url' => $menu->url, 'children' => $branch($menu->id),
            ])->all();
        };

        return $branch('');
    }

    public function menuMetadata(string $path): ?object
    {
        return $this->repository->published('menus')->where('url', $path)
            ->where(fn ($query) => $query->whereNotNull('seo_title')->orWhereNotNull('seo_description'))
            ->orderBy('position')->orderBy('id')->first(['seo_title', 'seo_description']);
    }

    public function contact(Request $request): void
    {
        $this->repository->contact($request->validateWithBag('contact', [
            'name' => 'required|string|max:180', 'email' => 'required|email:rfc|max:254',
            'phone' => ['required', 'string', 'regex:/^(0|\+84)[0-9]{9,10}$/'], 'message' => 'required|string|min:10|max:5000',
        ], [
            'name.required' => 'Vui lòng nhập họ tên.', 'email.required' => 'Vui lòng nhập email.', 'email.email' => 'Email không hợp lệ.',
            'phone.required' => 'Vui lòng nhập số điện thoại.', 'phone.regex' => 'Số điện thoại phải bắt đầu bằng 0 hoặc +84 và gồm 10–11 chữ số.',
            'message.required' => 'Vui lòng nhập nội dung.', 'message.min' => 'Nội dung tối thiểu 10 ký tự.',
        ]));
    }

    public function subscribe(Request $request): void
    {
        $email = $request->validateWithBag('subscribe', ['email' => 'required|email:rfc|max:254'], [
            'email.required' => 'Vui lòng nhập email.', 'email.email' => 'Email không hợp lệ.',
        ])['email'];
        $this->repository->subscribe(mb_strtolower($email));
    }
}
