<?php

namespace Modules\Client\Repositories;

use Illuminate\Database\Query\Builder;
use Modules\Admin\Repositories\CmsRepository;

class WebsiteRepository
{
    public function __construct(private CmsRepository $repository) {}

    public function published(string $resource): Builder
    {
        $table = config("cms.resources.$resource.table");
        $query = $this->repository->table($resource)->where("$table.active", true);
        if (in_array($resource, ['posts', 'products'], true)) {
            $query->where(fn (Builder $posts) => $posts->whereNull("$table.published_at")->orWhere("$table.published_at", '<=', now()->toDateTimeString()));
        }
        if ($resource === 'products') {
            // Tin đăng chỉ hiển thị khi đã duyệt và còn hạn.
            $query->where("$table.status", 'approved')
                ->where(fn (Builder $live) => $live->whereNull("$table.expires_at")->orWhere("$table.expires_at", '>', now()->toDateTimeString()));
        }

        return $query;
    }

    public function contact(array $fields): void
    {
        $this->repository->save('contacts', null, $fields + ['status' => 'new']);
    }

    public function subscribe(string $email): void
    {
        $this->repository->table('subscribers')->upsert(
            [['email' => $email, 'active' => true, 'created_at' => now(), 'updated_at' => now()]],
            ['email'],
            ['active', 'updated_at'],
        );
    }
}
