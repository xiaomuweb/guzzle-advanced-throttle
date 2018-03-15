

# hamburgscleanest/guzzle-advanced-throttle

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]

A Guzzle middleware that can throttle requests according to (multiple) defined rules. 

It is also possible to define a caching strategy, 
e.g. get the response from cache when the rate limit is exceeded or always get a cached value to spare your rate limits.

> **Please be aware that the format of the configuration changed since 2.0.0!**  
> Check out [Usage](#Usage)

## Install

Via Composer

``` bash
$ composer require hamburgscleanest/guzzle-advanced-throttle
```

## Usage

### General use

Let's say you wanted to implement the following rules:

> **20** requests every **1 seconds**
>
> **100** requests every **2 minutes**


----------


1. First you have to define the rules in a `hamburgscleanest\GuzzleAdvancedThrottle\RequestLimitRuleset`:

#### Configuration in version 1.x.x

``` php
$rules = new RequestLimitRuleset([
        [
            'host'             => 'https://www.google.com',
            'max_requests'     => 20,
            'request_interval' => 1
        ],
        [
            'host'             => 'https://www.google.com',
            'max_requests'     => 100,
            'request_interval' => 120
        ]
    ]);
```

#### Configuration in version 2.x.x

``` php
$rules = new RequestLimitRuleset([
        'https://www.google.com' => [
            [
                'max_requests'     => 20,
                'request_interval' => 1
            ],
            [
                'max_requests'     => 100,
                'request_interval' => 120
            ]
        ]
    ]);
```


----------


2. Your handler stack might look like this:
``` php
 $stack = new HandlerStack();
 $stack->setHandler(new CurlHandler());
```


----------
3. Push the `handle()` function of `hamburgscleanest\GuzzleAdvancedThrottle\Middleware\ThrottleMiddleware` to the stack. 

> It should always be the first middleware on the stack.

``` php
 $stack->push((new ThrottleMiddleware($rules))->handle());
```
----------
5. Pass the stack to the client
``` php
$client = new Client(['base_uri' => 'https://www.google.com', 'handler' => $stack]);
```

Either the `base_uri` has to be the same as the defined host in the rules array or you have to request absolute URLs for the middleware to have an effect.

``` php
// relative
$response = $client->get('test');

// absolute
$response = $client->get('https://www.google.com/test');
```

----------

### Caching

----------

#### Beforehand

Responses with an error status code `4xx` or `5xx` will not be cached (even with `force-cache` enabled)! 

----------

#### Available storage adapters

##### `array` (default)

Works out of the box.

----------

##### `laravel` (Illuminate/Cache) - *recommended*

You need to provide a config (`Illuminate\Config\Repository`) for this adapter.

###### Config array structure

In this example we are using the `file` driver:

``` php
    'cache' => [
        'driver'  => 'file',
        'options' => [
            'path' => './cache'
        ]
    ]
```

The options array accepts everything the normal laravel driver knows. 
So you could pass a `connection` for the `redis` driver for example:

``` php
    'cache' => [
        'driver'  => 'redis',
        'options' => [
            'connection' => 'default'
        ]
    ]
```

----------

###### Pass the config repository in the constructor of RequestLimitRuleset

``` php
$rules = new RequestLimitRuleset(
    [ ... ], 
    'cache', // caching strategy
    'laravel', // storage adapter
    new Repository(require '../config/laravel-guzzle-limiter.php') // config repository
    );
```

----------

> The same adapter will be used to store the internal request timers.

----------

##### The adapters can be defined in the rule set.

``` php
$rules = new RequestLimitRuleset(
    [ ... ], 
    'cache', // caching strategy
    'array' // storage adapter
    );
```

----------

#### Without caching - `no-cache`

Just throttle the requests. No caching is done.

``` php
$rules = new RequestLimitRuleset(
    [ ... ], 
    'no-cache', // caching strategy
    'array' // storage adapter
    );
```

----------

#### With caching (default) - `cache`

Use cached responses when your defined rate limit is exceeded. The middleware will try to fallback to a cached response before throwing `429 - Too Many Requests`.

``` php
$rules = new RequestLimitRuleset(
    [ ... ], 
    'cache', // caching strategy
    'array' // storage adapter
    );
```

----------

#### With forced caching - `force-cache`

Always use cached responses when available to spare your rate limits. As long as there is a response in cache for the current request it will return the cached response. It will only actually fire the request when it is not cached. If there is no cached response and the request limits are also reached, it will throw `429 - Too Many Requests`.

``` php
$rules = new RequestLimitRuleset(
    [ ... ], 
    'force-cache', // caching strategy
    'array' // storage adapter
    );
```

----------

### Wildcards

> Available in version 2.x.x and higher

If you want to define the same rules for multiple different hosts, you can use wildcards.
A possible use case can be subdomains:

``` php
$rules = new RequestLimitRuleset([
        'https://www.{subdomain}.mysite.com' => [
            [
                'max_requests'     => 50,
                'request_interval' => 2
            ]
        ]
    ]);
```

This `host` will match `https://www.en.mysite.com`, `https://www.de.mysite.com`, `https://www.fr.mysite.com`, etc.

----------

## Changes

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

----------

## Testing

``` bash
$ composer test
```

----------

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

----------

## Security

If you discover any security related issues, please email chroma91@gmail.com instead of using the issue tracker.

----------

## Credits

- [Timo Prüße][link-author]
- [Andre Biel][link-andre]
- [All Contributors][link-contributors]

----------

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/hamburgscleanest/guzzle-advanced-throttle.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/hamburgscleanest/guzzle-advanced-throttle/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/hamburgscleanest/guzzle-advanced-throttle.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/hamburgscleanest/guzzle-advanced-throttle.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/hamburgscleanest/guzzle-advanced-throttle.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/hamburgscleanest/guzzle-advanced-throttle
[link-travis]: https://travis-ci.org/hamburgscleanest/guzzle-advanced-throttle
[link-scrutinizer]: https://scrutinizer-ci.com/g/hamburgscleanest/guzzle-advanced-throttle/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/hamburgscleanest/guzzle-advanced-throttle
[link-downloads]: https://packagist.org/packages/hamburgscleanest/guzzle-advanced-throttle
[link-author]: https://github.com/Chroma91
[link-andre]: https://github.com/karllson
[link-contributors]: ../../contributors
