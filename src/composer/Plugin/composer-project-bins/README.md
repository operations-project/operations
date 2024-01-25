# Composer Project Bin Scripts

Composer vendor binaries allow dependencies to install scripts to your composer `bin-dir`.

This plugin allows you to define composer bin scripts in a top level project.

## Usage

1. Install the plugin.

    ```
    $ composer require operations/composer-project-bins
    ```
2. Create a script file.
    ```shell
    #!/usr/bin/env bash
    # File: scripts/hello-world
    echo "Hello World!"
    echo "You are here:"
    pwd
    ```
3. Add to `composer.json`:
    ```json
    {
      "bin": {
        "scripts/hello-world"
      }
    }
    ```
4. Run composer install:

    ```
    $ composer install
    ```

5. Run your script from the composer bin path:
    ```shell
    ./vendor/bin/hello-world
    ```

    Or, if you set PATH, just use the command.

    ```shell
    PATH=$PATH:./vendor/bin
    hello-world
    ```

## About

- Created by: [Jon Pugh](https://github.com/jonpugh)
