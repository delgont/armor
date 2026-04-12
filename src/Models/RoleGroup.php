<?php
/**
 * Delgont Armor
 *
 * @link      https://github.com/delgont/armor
 * @copyright Copyright (c) 2024 - Present Delgont Technologies Co. Ltd
 * @license   MIT License (https://opensource.org/licenses/MIT)
 *
 */

namespace Delgont\Armor\Models;

use Illuminate\Database\Eloquent\Model;

use Delgont\Armor\Models\Role;

class RoleGroup extends Model
{
    protected $fillable = ['name'];

    /**
     * A permission may belong to specific group.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function roles()
    {
        return $this->hasMany(Role::class, 'role_group_id');
    }

}
