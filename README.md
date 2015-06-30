LswDefaultRoutingBundle
=======================

The LswDefaultRoutingBundle adds default routing to your Symfony2 application.
Default routing adds a route naming scheme to the configured bundles. This
forces consistent naming of routes and simplifies both programming and debugging.

## Requirements

* Symfony 2.1+

## Installation

Installation is broken down in the following steps:

1. Download LswDefaultRoutingBundle using composer
2. Enable the Bundle
3. Add a default route into your routing.yml
4. Check whether the default routes are added or not

### Step 1: Download LswDefaultRoutingBundle using composer

Add LswDefaultRoutingBundle in your composer.json:

```js
{
    "require": {
        "leaseweb/default-routing-bundle": "*",
        ...
    }
}
```

Now tell composer to download the bundle by running the command:

``` bash
$ php composer.phar update leaseweb/default-routing-bundle
```

Composer will install the bundle to your project's `vendor/leaseweb` directory.

### Step 2: Enable the bundle

Enable the bundle in the kernel:

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Lsw\DefaultRoutingBundle\LswDefaultRoutingBundle(),
    );
}
```

### Step 3: Add a default route into your routing.yml

If you want to add default routing to a bundle you have to add configure this in the routing configuration file here:

```
app/config/routing.yml
```

These lines should be added to add default routing to the popular FosUserBundle:

```
FosUserBundle:
    resource: "@FOSUserBundle"
    prefix:   /
    type:     default
````

With the prefix option you can avoid namespace collisions.

### Step 4: Check whether the default routes are added or not

Use the following command to see whether or not the routes where added:

```
app/console router:debug
```

The entries in the router table that are added by the default router look like this:

```
[router] Current routes
Name                                                   Method Pattern
fos_user.user.login_check                              ANY    /user/login_check.{_format}
fos_user.user.logout                                   ANY    /user/logout.{_format}
fos_user.user.login                                    ANY    /user/login.{_format}
...
```

## Usage

### Default routing

When you create an action within a controller, you do not have to add a route 
for the action to the routing configuration. This is done automatically.

### Default templating

When you create an action within a controller, you do not have to specify the template
using the @Template directive. This is done automatically.

### Relative routing

If your current route is 'fos_user.user.index' and you use a route 'view' (that does not exist).
The relative routing feature will automatically search for 'fos_user.user.view'.

If your current route is 'fos_user.user.index' and you use a route 'group.view' (that does not exist).
The relative routing feature will automatically search for 'fos_user.group.view'.

#### In the Controller

When you are in the indexAction() in the Controller/UserController.php file and you want
to redirect to the viewAction you can use:

```
$this->redirect($this->generateUrl('view', compact('id')))
```

#### In the Twig template

When you are creating a link to a specific user from the index template in the 
Resources/views/User/index.html.twig file you can use:

```
<a href="{{ path('view', {id: id}) }}">View</a>
```
