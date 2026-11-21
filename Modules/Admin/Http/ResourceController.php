<?php

namespace Modules\Admin\Http;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Modules\Admin\Repositories\CmsRepository;
use Modules\Admin\Services\CmsService;

class ResourceController
{
    public function __construct(private CmsRepository $repository, private CmsService $service) {}

    public function index(Request $request, string $resource): View
    {
        $query = mb_substr(trim($request->string('q')->toString()), 0, 120);
        if ($resource === 'menus') {
            $tree = $this->service->menuTree();
            $page = min(LengthAwarePaginator::resolveCurrentPage(), max(1, (int) ceil(count($tree) / 12)));
            $records = new LengthAwarePaginator(
                array_slice($tree, ($page - 1) * 12, 12), count($tree), 12, $page,
                ['path' => $request->url()],
            );
        } else {
            $filters = array_filter([
                'status' => array_key_exists((string) $request->query('status'), config('cms.listing_statuses')) ? $request->query('status') : null,
                'listing_type' => array_key_exists((string) $request->query('type'), config('cms.listing_types')) ? $request->query('type') : null,
            ]);
            $records = $this->service->records($resource, $query, $filters);
        }

        $categories = in_array($resource, ['posts', 'products'], true) ? $this->service->menuCategories($resource) : [];
        $statusCounts = $resource === 'products'
            ? $this->repository->table('products')->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->all() : [];
        $packages = $resource === 'products' ? $this->repository->table('packages')->pluck('name', 'id')->all() : [];

        return view('admin::list', compact('resource', 'records', 'query', 'categories', 'statusCounts', 'packages'));
    }

    public function form(string $resource, ?int $id = null): View
    {
        abort_if($id === null && in_array($resource, ['contacts', 'subscribers'], true), 405);
        $record = $id === null ? null : $this->repository->find($resource, $id);
        $parents = $resource === 'menus' ? $this->service->menuChoices($id) : [];

        $categories = in_array($resource, ['posts', 'products'], true) ? $this->service->menuCategories($resource) : [];

        return view(in_array($resource, ['posts', 'products'], true) ? 'admin::editorial-form' : 'admin::form', compact('resource', 'record', 'parents', 'categories'));
    }

    public function save(Request $request, string $resource, ?int $id = null): RedirectResponse
    {
        $this->service->save($request, $resource, $id);

        return to_route('admin.index', $resource)->with('status', 'Đã lưu thay đổi.');
    }

    public function delete(Request $request, string $resource, int $id): RedirectResponse
    {
        $this->service->delete($request, $resource, $id);

        return to_route('admin.index', $resource)->with('status', 'Đã xóa mục đã chọn.');
    }
}
