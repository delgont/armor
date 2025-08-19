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

## Armor Audit Logging

`Armor` provides a flexible and structured audit logging system for Laravel. It allows you to log user activities, store before/after snapshots of data, add contextual links, and optionally capture request information. The system is backward compatible and works with existing code without breaking changes.

---

### Features
- Log user actions with custom messages.
- Store `before` and `after` snapshots of data.
- Include structured links (e.g., "View", "Undo") in logs.
- Automatically capture request information (method, URL, IP, user-agent).
- Fully backward compatible: old code works without links or snapshots.
- Easy to use via helper function or service.

---

#### Using the Helper Functions

Basic usage:

```php
audit_log(
    user: auth()->user(),
    action: 'Student Registered',
    message: 'Registered a new student'
);

audit_log(
    user: auth()->user(),
    action: 'Updated Student',
    message: 'Updated student details',
    before: $oldData,
    after: $newData
);

audit_log(
    user: auth()->user(),
    action: 'Registered Student',
    message: 'New student added',
    after: $student->toArray(),
    links: [
        ['text' => 'View Student', 'url' => route('students.show', $student->id)],
        ['text' => 'Undo', 'url' => route('students.undo', $student->id)]
    ]
);

audit_log(
    user: auth()->user(),
    action: 'Student Registered',
    message: 'Registered new student',
    requestData: [
        'method' => $request->method(),
        'url' => $request->fullUrl(),
        'ip' => $request->ip(),
        'user_agent' => $request->header('User-Agent')
    ]
);
```

#### Using the Service Class


```php
use Delgont\Armor\Services\AuditLogger;

AuditLogger::log(
    user: $user,
    action: 'Action Title',
    message: 'Optional message',
    requestData: ['method'=>'POST','url'=>route('route.name')],
    before: $oldData,
    after: $newData,
    links: [
        ['text'=>'View', 'url'=>route('route.view')],
        ['text'=>'Undo', 'url'=>route('route.undo')]
    ]
);
```

#### Displaying Logs in Blade

```php
<p>{{ $log->message }}</p>

@if(!empty($log->links))
    <ul>
        @foreach($log->links as $link)
            <li><a href="{{ $link['url'] }}">{{ $link['text'] }}</a></li>
        @endforeach
    </ul>
@endif
```


👉 **Read full documentation**: [https://delgont.github.io/armor-docs](https://delgont.github.io/armor-docs)

📖 **Developed by**: [Stephen Okello](https://github.com/stephenokelloug)
