<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\MissingAttributeException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\Uid\Ulid;

/**
 * Models that appear in URLs are addressed by a ULID instead of their auto-increment id, so nobody
 * can walk through records by counting up. The id stays the primary key for relations.
 *
 * The ULID is 128 random bits written in ULID notation (26 lowercase characters). It is not made
 * with Str::ulid(): within one millisecond that only adds 1 to the previous value, and its first 48
 * bits give away when the record was created. An invalid key is rejected before the query runs.
 *
 * Old links keep working: an auto-increment id (from before URLs used ULIDs) or a replaced ULID
 * (kept in ulid_lama) redirects to the current URL, but only for someone allowed to view the
 * record. Everyone else gets the same 404 as for a key that never existed.
 */
trait RoutesByUlid
{
    use HasUlids;

    public function newUniqueId(): string
    {
        return strtolower((string) Ulid::fromBinary(random_bytes(16)));
    }

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

    /**
     * A query that selected columns without "ulid" would build a URL without a key and break the
     * page. Outside production strict mode already throws for that; in production the ULID is
     * loaded here instead and the missed select is reported.
     */
    public function getRouteKey(): mixed
    {
        if ($this->exists && ! array_key_exists('ulid', $this->attributes) && ! static::preventsAccessingMissingAttributes()) {
            report(new MissingAttributeException($this, 'ulid'));

            $this->attributes['ulid'] = $this->newQueryWithoutScopes()->whereKey($this->getKey())->value('ulid');
            $this->syncOriginalAttribute('ulid');
        }

        return parent::getRouteKey();
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        if ($field !== null) {
            return parent::resolveRouteBinding($value, $field);
        }

        return (Str::isUlid($value) ? parent::resolveRouteBinding($value) : null)
            ?? $this->redirectFromOldRouteKey($this->newQuery(), $value);
    }

    /**
     * Same as resolveRouteBinding() for a ULID-addressed child in a scoped route
     * (/tugas/{tugas}/lampiran/{lampiran}); access to the child is decided by this parent.
     */
    public function resolveChildRouteBinding($childType, $value, $field): ?Model
    {
        $relasi = $this->{$this->childRouteBindingRelationshipName($childType)}();

        if ($field !== null || ! in_array(RoutesByUlid::class, class_uses_recursive($relasi->getRelated()))) {
            return parent::resolveChildRouteBinding($childType, $value, $field);
        }

        return (Str::isUlid($value) ? parent::resolveChildRouteBinding($childType, $value, null) : null)
            ?? $this->redirectFromOldRouteKey($relasi, $value, $this);
    }

    /**
     * Looks the value up as an old key and, when it belongs to a record the user may view (or, for a
     * child, whose parent they may view), redirects (301) to the same URL with the current ULID.
     *
     * @param  Builder<Model>|Relation<Model, Model, mixed>  $query
     *
     * @throws HttpResponseException
     */
    private function redirectFromOldRouteKey(Builder|Relation $query, mixed $value, ?Model $parent = null): null
    {
        $model = match (true) {
            is_string($value) && ctype_digit($value) => $query->whereKey($value)->first(),
            Str::isUlid($value) => $query->where('ulid_lama', $value)->first(),
            default => null,
        };

        if ($model === null || Gate::denies('view', $parent ?? $model)) {
            return null;
        }

        $route = request()->route();
        $parameters = $route->parameters();
        $parameters[array_search($value, $parameters, true)] = $model;

        $url = url()->toRoute($route, $parameters, true);
        $queryString = request()->getQueryString();

        throw new HttpResponseException(redirect()->to($queryString ? "{$url}?{$queryString}" : $url, 301));
    }
}
