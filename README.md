<p align="left"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400"></a></p>

## Laravel Authentication Backend
`Composer` `Laravel Framework 6.0+`

## Introduction

Laravel authentication backend that provides the following features.
- [x] Email or username authentication
- [x] Access control using roles and permissions.
- [x] Access control using roles and permissions.

## Installation

``` composer require delgont/auth ```

``` php artisan vendor:publish  --multiauth-config```

---


## Multi Username Authentication
`username` ` email`

1. Login Controller.
> *Create your custom login controller and use* `Delgont\Auth\Concerns\MultiAuthCredentials` . *this will overide the credentials function*


```
<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Delgont\Auth\Concerns\MultiAuthCredentials;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller - Multi Authentication using email or username
    |--------------------------------------------------------------------------
    | Use Delgont\Auth\Concerns\MultiAuthCredentials trait
    | You must override the credentials and username functions as shown below
    |
    */
    use AuthenticatesUsers, MultiAuthCredentials;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    protected function credentials(Request $request)
    {
        return $this->multiAuthCredentials($request);
    }

    public function username()
    {
        return 'username_email';
    }
}
```

2. Your login View.

```php
<input id="username_email" type="text" class="form-control @error('username_email') is-invalid @enderror" name="username_email" value="{{ old('username_email') }}" required autocomplete="username_email" autofocus>
@error('username_email')
  <span class="invalid-feedback" role="alert">
      <strong>{{ $message }}</strong>
  </span>
@enderror
```

---

## Access Control
Regulate access to your laravel systems resources, features and functionality.

### Access control basing on user type

<img src="UserTypes-Access-Control.jpg" width="700" />

1. add `usertype` & `user_id` columns to your authenticatable migration 

```php
<?php
..............
Schema::table('users', function (Blueprint $table) {
    $table->nullableMorphs('user');
});

```

2. Add `Delgont\Auth\Concerns\HasUserTypes` Trait to user model.

```php
<?php

namespace App;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;


use Delgont\Cms\Notifications\Auth\ResetPassword as ResetPasswordNotification;

use Delgont\Auth\Concerns\HasUserTypes;



class User extends Authenticatable
{
    use Notifiable, HasUserTypes, ModelHasPermissions, ModelHasSingleRole;
```

3. Your usertype models

```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    public function user()
    {
        return $this->morphOne('App\User', 'user');
    }
}
```

User can have single role or multiple roles


`Using role middleware to restrict access`

Use `Delgont\Auth\Concerns\ModelHasRoles` trait on your authenticable model

```php
<?php

namespace App;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

use Delgont\Auth\Concerns\ModelHasRoles;

class User extends Authenticatable
{
    /*
    | Use Delgont\Auth\Concerns\ModelHasRoles trait
    |
    */
    use ModelHasRoles;


}

```

> Assigning roles

```php
# Giving role using role names
$model->assignRole(['admin','accountant']);
auth()->user()->assignRole(['admin','accountant']);
```
Protecting routes using the role middleware
```php
Route::get('/test', 'TestController@test')->middleware('role:admin');
Route::get('/test', 'TestController@test')->middleware('role:admin|hello');
```


`Using permission middleware to restrict access`

```php
Route::get('/momo', 'Momo@index')->name('momo')->middleware('permission:access_momo_dashboard');
```

`Configure your default permissions in the permissions configuration file`

```
<?php

return [
    'delimiter' => '|',

    'permissions' => [
      'manage_users',
      'access_momo_dashbaord'
    ]
];
`


```

## Artisan Commands

> Roles

```composer
php artisan make:roleRegistrar Roles/ExampleRoleRegistrar
```

```composer
php artisan role:sync
```