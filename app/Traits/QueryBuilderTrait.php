<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

trait QueryBuilderTrait
{
    protected mixed $modelClass;

    public function defaultAllowFilter($query, Request $request): void
    {
        $filters = $request->get('filter');
        $filter = [];
        if (! empty($filters)) {
            foreach ($filters as $k => $item) {
                $filter[] = AllowedFilter::exact($k);
            }
        }
        $query->allowedFilters($filter);
    }

    public function defaultQuery($query, Request $request): void
    {
        $this->defaultAllowFilter($query, $request);
        $query->allowedAppends(! empty($request->append) ? explode(',', $request->get('append')) : []);
        $query->allowedIncludes(! empty($request->include) ? explode(',', $request->get('include')) : []);
        $query->allowedSorts($request->get('sort'));
    }

    public function filterBetweenDate($query, $start, $end, string $column = 'created_at'): void
    {
        if (! empty($start)) {
            $start = (new Carbon($start))->format('Y-m-d 00:00:00');
            $query->where($column, '>=', $start);
        }

        if (! empty($end)) {
            $end = (new Carbon($end))->format('Y-m-d 23:59:59');
            $query->where($column, '<=', $end);
        }
    }

    public function defaultAppendAndInclude($model, Request $request): void
    {
        if ($request->filled('append')) {
            $model->append(explode(',', $request->get('append')));
        }
        if ($request->filled('include')) {
            $model->load(explode(',', $request->get('include')));
        }
    }

    public function search($query, Request $request, array $columns = ['name'], string $key = 'search', string $table = ''): void
    {
        if ($request->filled($key)) {
            $search = $request->get($key);
            $query->where(function (Builder $query) use ($search, $columns, $table) {
                foreach ($columns as $i => $column) {
                    $column = empty($table) ? $column : $table.$column;
                    if ($i == 0) {
                        $query->where($column, 'ILIKE', "%$search%");
                    } else {
                        $query->orWhere($column, 'ILIKE', "%$search%");
                    }
                }

            });
        }
    }

    public function generateQuery(Request $request): QueryBuilder
    {
        $query = QueryBuilder::for($this->modelClass);
        $this->defaultQuery($query, $request);

        return $query;
    }
}
