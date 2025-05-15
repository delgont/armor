![Delgont Armor Banner](https://raw.githubusercontent.com/delgont/armor/main/banner.jpg)

## Armor – Laravel Role & Permission System

**Armor** is a powerful, flexible, and cache-friendly permission and role management package for Laravel applications. Designed with performance in mind, Armor allows you to define roles and permissions, assign them to users, and protect routes or actions effortlessly.

🔒 Built for speed  
⚙️ Highly customizable  
📦 Supports permission groups, caching, syncing, and more

#### Examples

```php
Route::post('/posts/create', 'PostController@store')->middleware(['permission:can-create-post']);
```

```php
@can('can-view-posts')
    html here
@endcan
```

```php
<?php

/**
 * Permission Group
 */
namespace App;

use Delgont\Armor\PermissionRegistrar;

class ClientPermissionRegistrar extends PermissionRegistrar
{
    // Permissions related to client management
    const CAN_MANAGE_CLIENTS = 'can_manage_clients';
    const CAN_VIEW_CLIENTS = 'can_view_clients';
    const CAN_CREATE_CLIENTS = 'can_create_clients';
    const CAN_UPDATE_CLIENTS = 'can_update_clients';
    const CAN_DELETE_CLIENTS = 'can_delete_clients';

    // Permissions related to licenses
    const CAN_MANAGE_LICENSES = 'can_manage_licenses';
    const CAN_VIEW_LICENSES = 'can_view_licenses';
    const CAN_ASSIGN_LICENSES = 'can_assign_licenses';

    // Permissions related to client logs
    const CAN_VIEW_CLIENT_LOGS = 'can_view_client_logs';
    const CAN_DELETE_CLIENT_LOGS = 'can_delete_client_logs';

    /**
     * Provide descriptions for each permission.
     *
     * @return array
     */
    public function descriptions(): array
    {
        return [
            // Client management descriptions
            self::CAN_MANAGE_CLIENTS => 'Allows managing all aspects of client accounts.',
            self::CAN_VIEW_CLIENTS => 'Allows viewing client information.',
            self::CAN_CREATE_CLIENTS => 'Allows creating new client accounts.',
            self::CAN_UPDATE_CLIENTS => 'Allows updating client account details.',
            self::CAN_DELETE_CLIENTS => 'Allows deleting client accounts.',

            // License management descriptions
            self::CAN_MANAGE_LICENSES => 'Allows full management of licenses.',
            self::CAN_VIEW_LICENSES => 'Allows viewing license details.',
            self::CAN_ASSIGN_LICENSES => 'Allows assigning licenses to clients.',

            // Client log management descriptions
            self::CAN_VIEW_CLIENT_LOGS => 'Allows viewing logs related to client activities.',
            self::CAN_DELETE_CLIENT_LOGS => 'Allows deleting client logs from the system.',
        ];
    }
}
```

👉 **Read full documentation**: [https://delgont.github.io/armor-docs](https://delgont.github.io/armor-docs)

📖 **Developed by**: [Stephen Okello](https://github.com/stephenokelloug)
