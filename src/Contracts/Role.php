<?php

namespace Delgont\Armor\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

interface Role
{

    /**
     * Role may have permissions
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions() : BelongsToMany;

    
}
