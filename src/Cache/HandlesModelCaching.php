<?php
namespace Delgont\Armor\Cache;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;



trait HandlesModelCaching
{
    /**
     * Indicates wheather to get data from the cache storage
     * @var bool
     */
    protected $fromCache = false;

    /**
     * How long data should stay in cache
     * @var int
     */
    protected $cacheExpiry = '1440';

    /**
     * whether to rember the data or not
     * @var bool
     */
    protected $remember = false;

    protected $customCacheKey = null;

    protected $cachePrefix;

    protected $model;

    /**
     * Get cache expiry time
     * @var int
     */
    public function getCacheExpiry()
    {
        return $this->cacheExpiry;
    }


     /**
     * Set cache expiry time
     * @var this
     */
    public function setCacheExpiry($ttl)
    {
        $this->cacheExpiry = $ttl;
        return $this;
    }

    /**
    * Whether to get data from cache if it exists
    * @var this
    */
    public function fromCache( )
    {
        $this->fromCache = true;
        return $this;
    }

     /**
    * Whether to remember the data
    * @var this
    */
    public function remember( $customCacheKey = null )
    {
        $this->remember = true;
        $this->customCacheKey = $customCacheKey;
        return $this;
    }

    public function getCachePrefix()
    {
        return $this->cachePrefix;
    }


    public function setCachePrefix($prefix)
    {
        $this->cachePrefix = $prefix;
        return $this;
    }

    /**
     * Get hydrated model from cache
     * 
     * @param string $key
     * @param Illuminate\Database\Eloquent\Model $model
     * 
     * @return Illuminate\Database\Eloquent\Model
     */
    protected function getModelFromCache($key, Model $model = null) : ? Model 
    {
        $cached = Cache::get($key);
        if($cached){
            $models = (!is_null($model)) ? $model::hydrate([$cached]) : $this->model::hydrate([$cached]);
            return $models[0] ?? null;
        }
        return null;
    }


    /**
     * Store model in cache
     * 
     * @param Illuminate\Database\Eloquent\Model $model
     * @param string $key
     * 
     * @return bool
     */
    public function storeModelInCache( Model $model, $key = null )
    {
        return $this->writeToCache( $this->generateModelUnitCacheKey( $model, $key ), $model->toArray() );
    }

    public function storeCollectionInCache( Collection $collection, $cacheKey) : bool
    {
        return $this->writeToCache($cacheKey, $collection);
    }
   
    protected function storeInCache($key, $value) : bool
    {
        return $this->writeToCache( $key, $value );
    }

    protected function writeToCache ( string $key, $value ) : bool
    {
        Cache::put($key, $value, now()->addMinutes($this->cacheExpiry));
        return true;
    }

    public function generateModelUnitCacheKey ( Model $model, $key = null ) : string 
    {
        return ($key) ? $key : $this->getCachePrefix($model).':'.$model->getKey();
    }


     /**
     * Store length aware paginator in cache
     * 
     * @param Illuminate\Pagination\LengthAwarePaginator $data
     * @param string $cacheKey
     * 
     * @return bool
     */
    public function storeLengthAwarePaginatorInCache( LengthAwarePaginator $data, $cacheKey) : bool
    {
        return $this->writeToCache($cacheKey, $data);
    }




}