Phulp Webpack
=============

It's a third-party project that provides a means to pipe files to
[Webpack](https://github.com/webpack/webpack/).

There are some inconsistencies with how Webpack usually works since it requires
actual files and entry points, so the plugin creates temporary files in the project
directory to have paths resolve correctly. The main difference is that you can't
supply a config file. That just means additional complexity which would be better
handled by calling `shell_exec('webpack --config webpack.config.js')`. Another
difference is that the config option `output.path` only works with relative paths
rather than absolute paths since the output path is decided by what you pipe the
files to with Phulp.

## Installation

```bash
composer require nsrosenqvist/phulp-webpack
```

## Usage

First argument accepts a config array that will be converted into JSON. The second
argument is an optional path to the Webpack executable in case it's not globally
in your `$PATH`.

```php
<?php

use NSRosenqvist\Phulp\Webpack;

$phulp->task('scripts', function ($phulp) {
    $phulp->src(['assets/scripts/'], 'main.js')
        ->pipe(new Webpack([
            'module' => [
                'rules' => [
                    [
                        'test' => '/\.js$/',
                        'exclude' => '/(node_modules|bower_components)/',
                        'use' => [
                            'loader' => 'babel-loader',
                            'options' => [
                                'presets' => ['@babel/preset-env'],
                            ],
                        ],
                    ],
                ],
            ],
        ]))
        ->pipe($phulp->dest('dist/scripts/'));
});
```

## License
MIT
