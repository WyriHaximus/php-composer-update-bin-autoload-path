# composer update bin autoload path

Composer plugin that updates the autoload path of bin files so you don't have to guess the location, you already know it

![Continuous Integration](https://github.com/wyrihaximus/php-composer-update-bin-autoload-path/workflows/Continuous%20Integration/badge.svg)
[![Latest Stable Version](https://poser.pugx.org/wyrihaximus/composer-update-bin-autoload-path/v/stable.png)](https://packagist.org/packages/wyrihaximus/composer-update-bin-autoload-path)
[![Total Downloads](https://poser.pugx.org/wyrihaximus/composer-update-bin-autoload-path/downloads.png)](https://packagist.org/packages/wyrihaximus/composer-update-bin-autoload-path)
[![Code Coverage](https://scrutinizer-ci.com/g/wyrihaximus/php-composer-update-bin-autoload-path/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/wyrihaximus/php-composer-update-bin-autoload-path/?branch=master)
[![Type Coverage](https://shepherd.dev/github/wyrihaximus/php-composer-update-bin-autoload-path/coverage.svg)](https://shepherd.dev/github/wyrihaximus/php-composer-update-bin-autoload-path)
[![License](https://poser.pugx.org/wyrihaximus/composer-update-bin-autoload-path/license.png)](https://packagist.org/packages/wyrihaximus/composer-update-bin-autoload-path)

## Install ##

To install via [Composer](http://getcomposer.org/), use the command below, it will automatically detect the latest version and bind it with `~`.

```
composer require wyrihaximus/composer-update-bin-autoload-path
```

## Usage ##

Define your tools bin like you normally would in `composer.json`:

```json
{
    "bin": [
        "bin/app-name"
    ]
}
```

This plugin expects `bin/app-name.source` to exist and will create `bin/app-name` based on that. The following example 
`bin/app-name.source`:

```php
#!/usr/bin/php
<?php declare(strict_types=1);

(function() {
    /**
     * Require Composer's autoloader
     */
    require_once '%s';
})();

(function() {
    /**
     * Execute the application
     */
    exit((function (): int {
        return 0;
    })());
})();
```

Will become `bin/app-name` when you run a composer install/update:

```php
#!/usr/bin/php
<?php declare(strict_types=1);

(function() {
    /**
     * Require Composer's autoloader
     */
    require_once '/home/wyrihaximus/Projects/WyriHaximus/php-composer-update-bin-autoload-path/vendor/autoload.php';
})();

(function() {
    /**
     * Execute the application
     */
    exit((function (): int {
        return 0;
    })());
})();
```

## License ##

Copyright 2020 [Cees-Jan Kiewiet](http://wyrihaximus.net/)

Permission is hereby granted, free of charge, to any person
obtaining a copy of this software and associated documentation
files (the "Software"), to deal in the Software without
restriction, including without limitation the rights to use,
copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following
conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.
