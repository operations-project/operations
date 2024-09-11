# Project Bin Scripts
## Your scripts in the Composer bin-dir.

This plugin allows you to add your own scripts as composer bins to projects.

It works just like dependent packages "bin" scripts work: a link is created from
your vendor bin directory to the script.

### Advantages:

1. All commands for your project can be run from the same directory, the vendor bin dir.
2. The composer bin dir and autoloader path are available via bash or PHP variables.
3. Scripts can use ensure they are calling exact versions of scripts such as `drush` or `npm` by including this path.

See [Composer "Vendor Binaries" Documentation](https://getcomposer.org/doc/articles/vendor-binaries.md#finding-the-composer-autoloader-from-a-binary) for more information.

## Usage

1. Install the plugin.

    ```
    $ composer require operations/project-bin-scripts
    ```
2. Create a script file.
    ```shell
    #!/usr/bin/env bash
    # File: scripts/hello-world

    # If your script wants to rely on the other scripts in bin-dir, check for $COMPOSER_RUNTIME_BIN_DIR
    if [[ -z $COMPOSER_RUNTIME_BIN_DIR ]]; then
      echo "ERROR: The COMPOSER_RUNTIME_BIN_DIR environment variable is missing. Run this script from the composer vendor bin directory."
      exit 1
    fi

    # Set the PATH variable to include COMPOSER_RUNTIME_BIN_DIR to allow calling
    # other bin scripts directly.
    PATH=$PATH:$COMPOSER_RUNTIME_BIN_DIR

    echo "Hello World!"
    echo "You are here:"
    pwd

    # To confirm this works, try using "which"
    echo "You are using this drush: "
    which drush
    drush --version

    ```
3. Add to `composer.json`:
    ```json
    {
      "bin": [
        "scripts/hello-world"
      ]
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
## NOTE about composer schema for "bin"

This is the same configuration used by `composer.json` schema for dependencies.
Using this same config here might cause some confusion.

The main reason this was used is so the plugin could use the exact same code
to install project binaries as it does dependency binaries.

If you think this is confusing and should change, please submit a pull request.

## About

- Created by: [Jon Pugh](https://github.com/jonpugh)
