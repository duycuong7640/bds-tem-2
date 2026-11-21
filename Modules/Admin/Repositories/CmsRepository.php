<?php

namespace Modules\Admin\Repositories;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class CmsRepository
{
    public function table(string $resource): Builder
    {
        $table = config("cms.resources.$resource.table");
        abort_unless(is_string($table), 404);

        return DB::table($table);
    }

    public function find(string $resource, int $id): object
    {
        $record = $this->table($resource)->find($id);
        abort_unless($record, 404);

        return $record;
    }

    public function save(string $resource, ?int $id, array $fields): void
    {
        $fields['updated_at'] = now();
        if ($id === null) {
            $this->table($resource)->insert($fields + ['created_at' => now()]);

            return;
        }

        $this->table($resource)->where('id', $id)->update($fields);
    }

    public function revokeSessions(int $userId): void
    {
        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))->table(config('session.table'))
                ->where('user_id', $userId)->delete();
        }
    }

    public function delete(string $resource, int $id): void
    {
        $this->table($resource)->where('id', $id)->delete();
    }
}
