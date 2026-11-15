<?php

namespace Delgont\Armor\Support;

use Closure;
use Delgont\Armor\Models\Permission;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PermissionCache
{
    public function store(): Repository
    {
        return Cache::store(config('armor.cache_store', 'file'));
    }

    /** Seconds. */
    public function ttl(): int
    {
        return (int) config('armor.cache_duration', 3600);
    }

    /** Global token. Rotating it invalidates every model's cached set. */
    public function epoch(): string
    {
        return $this->store()->rememberForever('armor:epoch', fn () => Str::random(16));
    }

    public function flushAll(): void
    {
        $this->store()->forever('armor:epoch', Str::random(16));
    }

    protected function subject(Model $model): string
    {
        return $model->getMorphClass().':'.$model->getKey();
    }

    protected function versionKey(Model $model): string
    {
        return 'armor:ver:'.$this->subject($model);
    }

    /** Per-model token. Rotating it invalidates one model. */
    public function version(Model $model): string
    {
        return $this->store()->rememberForever($this->versionKey($model), fn () => Str::random(16));
    }

    public function key(Model $model): string
    {
        return 'armor:perms:'.$this->epoch().':'.$this->subject($model).':'.$this->version($model);
    }

    public function remember(Model $model, Closure $loader): array
    {
        return $this->store()->remember($this->key($model), $this->ttl(), $loader);
    }

    public function flush(Model $model): void
    {
        $this->store()->forever($this->versionKey($model), Str::random(16));
    }

    /**
     * Does a permission with this name exist?
     * Only "yes" is cached, so a typo is never remembered and a
     * newly created permission is seen immediately.
     */
    public function permissionExists(string $name): bool
    {
        $key = 'armor:known:'.$this->epoch().':'.$name;

        if ($this->store()->has($key)) {
            return true;
        }

        if (Permission::whereName($name)->exists()) {
            $this->store()->put($key, true, $this->ttl());
            return true;
        }

        return false;
    }
}