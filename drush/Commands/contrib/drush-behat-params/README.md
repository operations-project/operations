# Drush Behat Params

A basic drush plugin that calls behat with `BEHAT_PARAMS` with URL, root and drush alias set automatically.

![img.png](img.png)

## Usage:

1. Install with composer:

       composer require operations/drush-behat-params`

2. Run with drush

       drush behat
    or

       drush @alias behat

Make sure `DRUSH_OPTIONS_URI` value is set so behat tests the right URL.

NOTE: For easy setting of `DRUSH_OPTIONS_URI` on Lando, see https://github.com/operations-platform/drupal-settings.

### Remote Aliases

You can use this command to run tests on a remote site from your local using [Drush Site Aliases](https://www.drush.org/12.x/site-aliases/).

For example, if you have an alias called `@test`, you can run your behat tests on it like so:

        $ drush @dev behat
        ------------- ---------------------------------------- -----------
        Drush Alias   URL                                      Root
        ------------- ---------------------------------------- -----------
        @self         test-projectcode.pantheonsite.io          /code/web
        ------------- ---------------------------------------- -----------
        ....


## What?

Behat uses either hard-coded config or a JSON blob in BEHAT_PARAMS to configure what site to test.

Instead of messing with ENV vars, this plugin sets BEHAT_PARAMS for you using the Drush alias information.

## Development


This tool is a part of the [Drupal Operations Project](https://drupal.org/project/operations).

Source code is maintained at https://git.drupalcode.org/project/operations/-/tree/1.x/drush/Commands/contrib/drush-behat-params

Issues can be filed at https://www.drupal.org/project/issues/operations?categories=All
