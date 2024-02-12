# Remote Bin Scripts
## Download things into the bin-dir.

This plugin allows you to specify URLs to download on `composer install` as scripts.

For example, you can download a binary or phar file into your `vendor/bin` directory.

### Advantages:

1. Add PHP tools as phar files to alleviate the need to align composer requirements.
2. Add scripts and tools written in other languages.
3. Integrates with `composer install`.

## Usage

1. Install the plugin.

    ```
    $ composer require operations/remote-bin-scripts
    ```
2. Add to `composer.json`:
    ```json
    {
      "extra": {
        "remote-scripts": {
          "vendor/bin/hello-world": "https://github.com/operations-platform/remote-bin-scripts"
        }
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
