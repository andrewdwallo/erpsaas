# ERPSAAS 

A Multi-tenant SaaS ERP Software System Package for Filament:

<hr style="background-color: #ebb304">

```
#### v2.x
> **Note** 
> Minimum **Filament** Requirement is now `2.13'.

## Installation

1. Clone the git repo:

```bash
git clone 
```

2. Add the `Spatie\Permission\Traits\HasRoles` trait to your User model(s):

```php
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles; //or HasFilamentShield

    // ...
}
```
3. Publish the `config` using:
```bash
php artisan vendor:publish --tag=filament-shield-config
```
4. Setup your configuration
```php
<?php

    return [

          'shield_resource' => [
              'slug' => 'shield/roles',
              'navigation_sort' => -1,
              'navigation_badge' => true,
              'navigation_group' => true,
              'is_globally_searchable' => false,
          ],

          'auth_provider_model' => [
              'fqcn' => 'App\\Models\\User'
          ],

          'super_admin' => [
              'enabled' => true,
              'name'  => 'super_admin',
              'define_via_gate' => false,
              'intercept_gate' => 'before' // after
          ],

          'filament_user' => [
              'enabled' => true,
              'name' => 'filament_user'
          ],

          'permission_prefixes' => [
              'resource' => [
                  'view',
                  'view_any',
                  'create',
                  'update',
                  'restore',
                  'restore_any',
                  'replicate',
                  'reorder',
                  'delete',
                  'delete_any',
                  'force_delete',
                  'force_delete_any',
              ],

              'page' => 'page',
              'widget' => 'widget',
          ],

          'entities' => [
              'pages' => true,
              'widgets' => true,
              'resources' => true,
              'custom_permissions' => false,
          ],

          'generator' => [
              'option' => 'policies_and_permissions'
          ],

          'exclude' => [
              'enabled' => true,

              'pages' => [
                  'Dashboard',
              ],

              'widgets' => [
                  'AccountWidget','FilamentInfoWidget',
              ],

              'resources' => [],
          ],

          'register_role_policy' => [
              'enabled' => true
          ],
    ];
```
4. Now run the following command to install shield:
```bash
php artisan shield:install
```

Follow the prompts and enjoy!

#### Resource Custom Permissions

You can add custom permissions for `Resources` through Config file.

#### Pages

If you have generated permissions for `Pages` you can toggle the page's navigation from sidebar and restricted access to the page. You can set this up manually but this package comes with a `HasPageShield` trait to speed up this process. All you have to do is use the trait in you pages:
```php
<?php

namespace App\Filament\Pages;

use ...;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class MyPage extends Page
{
    use HasPageShield;
    ...
}
```

📕 <b style="color:darkred">`HasPageShield` uses the `booted` method to check the user's permissions and makes sure to execute the `booted` page method in the parent page if exists.</b>

###### Pages Hooks

However if you need to perform some methods before and after the booted method you can declare the next hooks methods in your filament page.

```php
<?php

namespace App\Filament\Pages;

use ...;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class MyPage extends Page
{
    use HasPageShield;
    ...

    protected function beforeBooted : void() {
        ...
    }

    protected function afterBooted : void() {
        ...
    }

    /**
     * Hook to perform an action before redirect if the user
     * doesn't have access to the page.  
     * */
    protected function beforeShieldRedirects : void() {
        ...
    }
}
```

###### Pages Redirect Path

`HasPageShield` uses the `config('filament.path')` value by default to perform the shield redirection. If you need to overwrite the rediretion path, just add the next method to your page:

```php
<?php

namespace App\Filament\Pages;

use ...;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class MyPage extends Page
{
    use HasPageShield;
    ...

    protected function getShieldRedirectPath(): string {
        return '/'; // redirect to the root index...
    }
}
```

#### Widgets

if you have generated permissions for `Widgets` you can toggle their state based on whether a user have permission or not. You can set this up manually but this package comes with a `HasWidgetShield` trait to speed up this process. All you have to do is use the trait in you widgets:
```php
<?php

namespace App\Filament\Widgets;

use ...;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class IncomeWidget extends LineChartWidget
{
    use HasWidgetShield;
    ...
}
```

#### Role Policy

You can skip this if have set the `'register_role_policy' => true` in the config.
To ensure `RoleResource` access via `RolePolicy` you would need to add the following to your `AuthServiceProvider`:

```php
//AuthServiceProvider.php
...
protected $policies = [
    'Spatie\Permission\Models\Role' => 'App\Policies\RolePolicy',
];
...
```

#### Third-Party Plugins

Shield also generates policies and permissions for third-party plugins and to enforce the generated policies you will need to register them in your application's `AuthServiceProvider`:
```
...
class AuthServiceProvider extends ServiceProvider
{
    ...
    protected $policies = [
        ...,
        'Ramnzys\FilamentEmailLog\Models\Email' => 'App\Policies\EmailPolicy';

    ];
```
Same applies for models inside folders.

#### Translations 

Publish the translations using:

```bash
php artisan vendor:publish --tag="filament-shield-translations"
```

## Available Filament Shield Commands

#### `shield:doctor` 
- Show useful info about Filament Shield.

#### `shield:install` 
Setup Core Package requirements and Install Shield. Accepts the following flags:
- `--fresh`           re-run the migrations
- `--only`            Only setups shield without generating permissions and creating super-admin
  
#### `shield:generate`
Generate Permissions and/or Policies for Filament entities. Accepts the following flags: 
- `--all`                    Generate permissions/policies for all entities
- `--option[=OPTION]`        Override the config generator option(`policies_and_permissions`,`policies`,`permissions`)
- `--resource[=RESOURCE]`    One or many resources separated by comma (,)
- `--page[=PAGE]`            One or many pages separated by comma (,)
- `--widget[=WIDGET]`        One or many widgets separated by comma (,)
- `--exclude`                Exclude the given entities during generation
- `--ignore-config-exclude`  Ignore config `exclude` option during generation

#### `shield:super-admin` 
Create a user with super_admin role.
- Accepts an `--user=` argument that will use the provided ID to find the user to be made super admin.

#### `shield:upgrade` 
- Upgrade shield.


## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Bezhan Salleh](https://github.com/bezhanSalleh)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
