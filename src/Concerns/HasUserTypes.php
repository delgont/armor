<?php

namespace Delgont\Armor\Concerns;

trait HasUserTypes
{
    
    /**
     * get the actual User model
     */
    public function usertype()
    {
        return $this->morphTo(__FUNCTION__, 'user_type', 'user_id');
    }
}