<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUlids;

/**
 * Models that appear in URLs are addressed by a ULID (26 lowercase characters, 80 random bits)
 * instead of their auto-increment id, so nobody can walk through records by counting up. The id
 * stays the primary key for relations; the ULID is only generated on insert and used for route
 * binding — an invalid one is rejected before the query runs.
 */
trait RoutesByUlid
{
    use HasUlids;

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }
}
