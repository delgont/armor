<h1>Delgont Armor</h1>

Armor is a Laravel package that provides a powerful access control system through roles and permissions, along with flexible backend authentication. This package allows login using either email, username, phone number, or another identifier, based on your preference.

Furthermore, the package enables user type-based access control through the HasUserTypes trait, offering fine-grained control over user access to specific routes and Blade views.

## Features
1. User Type-based Access Control - Enables route and Blade access based on user types.
2. Multi-Authentication Credentials - Supports login with different fields, such as email, username, or phone number.
3. Role and Permission Management - Assign roles and permissions to control access at a more granular level.


`Email or username authentication` `Access control using roles and permissions.`

`Composer` `Laravel Framework 11.0+`

## Table Of Contents

<ul>
  <li><a href="#installation">Installation</a></li>
  <li><a href="#configuration">Armor Configuration</a></li>
  <li><a href="#multiauth">Multi-Credentials Authenticationion</a></li>
  <li><a href="#usertype">User Type-Based Access Control</a></li>
  <li><a href="#blade-directives">Blade Directives</a></li>
  <ul>
  <li><a href="#usertype-blade-directive">@usertype Blade Directive</a></li>
  <li><a href="#can-blade-directive">@can Blade Directive</a></li>
  <li><a href="#rolecan-blade-directive">@rolecan Blade Directive</a></li>
  </ul>
  <li><a href="#middlewares">Middlewares</a></li>
  <li><a href="#artisan-commands">Artisan Commands</a></li>

</ul>



<h2 id="installation">Installation</h2>

To install the Delgont/Armor package, use Composer:

``` composer require delgont/armor ```

After installation, publish the package configuration:

``` php artisan vendor:publish --provider="Delgont\Armor\ArmorServiceProvider" ```

This command will publish the config/armor.php file, where you can set up custom configurations.

---



<h2 id="configuration">Configuration</h2>


``` config/armor.php ```

<h5 style="color: #FF6347;">Permission Delimiter Configuration</h5>

```php
/**
 * Permission Demiliter
 * Defines the separator for listing multiple permissions in middleware. For example, 
 * setting permission:view-dashboard|edit-settings allows access if the user has
 * either permission.
 */
'permission_delimiter' => '|',
```

<h5 style="color: #FF6347;">Permission Registrars Configuration</h5>

```php
/**
 * Defines an array of classes that statically declare permissions for various user actions within your 
 * application. Each class listed here represents a group 
 * of permissions tied to specific user roles or functionalities.
 */
'permission_registrars' => [
    App\Permissions\ExamplePermissionRegistrar::class,
],
```

---

<h2 id="multiauth">Adding Multi-Credentials Authenticationion</h2>

To allow users to log in with different credentials, such as email or username, email or phone.

1. Import the Trait: In your LoginController, import `the MultiAuthCredentials trait` :

```php
use Delgont\Armor\Concerns\MultiAuthCredentials;
```

2.  Setup the Login Field: In your LoginController, set up a method to allow dynamic field selection, if you want  the user to login using phone or email then you can choose this function to retunr `phone_email` and this should be defined as the name for you login form input

```php
public function username()
{
    return 'username_email';
}
```

3. Define the `username` column name that will be used by Overriding  the getSecondaryColumn() function in your LoginController. This function specifies the second column that will be used along with email during authentication. By default, it returns 'name', but you can modify it to use the column you intend to use.

```php
 /**
 * Get the second colum that will be used with email and the second field by default name column defined in the user table
 * @return string
 */
protected function getSecondaryColumn ()
{
    return 'name';
}
```

4. Implement Multi-Credentials: Use the `multiAuthCredentials()` method to check the user’s login input and determine the field (username or email, phone or email) they are attempting to log in with. Override the credentials method in `LoginController` :

```php
protected function credentials(Request $request)
{
    return $this->multiAuthCredentials($request);
}
```

5. Login Form Input: Your login form should use the `username_email` field, which allows the user to enter their `username or email ` or `phone_email` if you want to allow login with `phone or email`

```php
<input type="text" class="form-control @error('username_email') is-invalid @enderror" id="username_email" name="username_email" placeholder="Username or Email" value="{{ old('username_email') }}" />
```

`LoginController`


```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Foundation\Auth\ThrottlesLogins;

use Delgont\Armor\Concerns\MultiAuthCredentials;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;


class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */
    use AuthenticatesUsers, MultiAuthCredentials, ThrottlesLogins;

    /**
     * Where to redirect users after login - You can redirect to users default home page
     *
     * @var string
     */
    protected $redirectTo;

    /**
     * Create a new controller instance. 
     *
     * @return void
     */
    public function __construct()
    {
        //Artisan::call('permission:sync');
        $this->middleware('guest')->except('logout');
    }

    /**
     * Override this method for multi user authentication to work
     */
    protected function credentials(Request $request)
    {
        return $this->multiAuthCredentials($request);
    }


   

    public function username()
    {
        return 'username_email';
    }

     /**
     * Get the second colum that will be used with email and the second field by default name column defined in the user table
     * @return string
     */
    protected function getSecondaryColumn ()
    {
        return 'name';
    }
    
}

```
<h2 id="usertype">User Type-Based Access Control</h2>

User type-based access control allows you to restrict specific routes and Blade templates based on user roles, such as "master" or "employee."


1. add usertype & user_id columns to your authenticatable migration

```php
<?php
..............
Schema::table('users', function (Blueprint $table) {
    $table->nullableMorphs('user');
});
```

2. Add Delgont\Auth\Concerns\HasUserTypes Trait to user model.

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
    use Notifiable, HasUserTypes;
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


<h2 id="blade-directives">Blade Directives</h2>

<h5 style="color: #FF6347;" id="usertype-blade-directive">@usertype Blade Directive</h5>

The @usertype Blade directive allows you to check if the currently authenticated user matches any of the specified user types. This directive can be used to conditionally render content based on the user's type, providing a flexible way to manage permissions and access control in your views.


<h6 style="color: #FF6347;" id="usertype-blade-directive">Usage</h6>

```php
@usertype('userType1|userType2')
    <p>You are authorized to access this section.</p>
@else
    <p>You do not have access to this section.</p>
@endusertype
```

<h5 style="color: #FF6347;" id="can-blade-directive">@can Blade Directive</h5>

<h6 style="color: #FF6347;">Usage</h6>

```php
@can('permissionone|permissiontwo')
    <p>You are authorized to access this section.</p>
@else
    <p>You do not have access to this section.</p>
@endcan
```

<h5 style="color: #FF6347;" id="rolecan-blade-directive">@rolecan Blade Directive</h5>


The @rolecan directive allows you to check if the authenticated user's role has the specified permissions before granting access to a particular section of your Blade view. This directive is particularly useful for managing access control based on user roles and their associated permissions.

Your Authenticatable models must be limited to a single role, use `Delgont\Armor\Concerns\ModelHasSingleRole` and migrations must have `role_id` 



<h6 style="color: #FF6347;"">Usage</h6>


```php
@rolecan('permissionone|permissiontwo')
    <p>You are authorized to access this section your role has the necessary permissions.</p>
@elserolecan
    <p>Your role does not have the necessary permission to access this resource</p>
@endrolecan
```










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

<h2 id="middlewares" style="color: #1E90FF;">Middlewares</h2>

### 1. Track Page Access

`track.page-access:page-name`

This middleware is responsible for tracking the times a page has been access on a Laravel application. It logs information about page views, including the IP address, User-Agent, and visit count for each page. If a user accesses the same page multiple times, the middleware will update the count and store the latest User-Agent information.

#### Usage
To use this middleware, it should be registered in your app/Http/Kernel.php file or applied directly to specific routes in the routes/web.php file.

```php
Route::get('/page', function ($pageName) {
    // Your page logic here
})->middleware('track.page-access:page-name');
```

---

<h2 id="artisan-commands" style="color: #1E90FF;">Artisan Commands</h2>


> Roles

```cmd
php artisan make:roleRegistrar Roles/ExampleRoleRegistrar
```

```cmd
php artisan role:sync
```

```cmd
php artisan permissions:sync
```

```cmd
php artisan permissions:give-all {userId} Modules\Applicant\Entities\Applicant
```

```php
php artisan armor:install
```


