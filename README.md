# Drupal Operations
## Tools for Operating Drupal Sites

Welcome to Drupal Operations: a suite of modules and tools to build ops platforms out of Drupal.

This repository serves as the main development codebase for all of the Operations submodules and packages.

For more information, see the [Drupal.org Project Page](https://www.drupal.org/project/operations).

## Components

### Drupal Modules

Whenever possible, the Drupal Operations Modules can be installed independently of one another. The Site module is designed to go into every site, so that it can report back to Site Manager.

1. [Site Entity Module](https://www.drupal.org/project/site) - https://www.drupal.org/project/site

   Provides advanced status and information about any Drupal site. Connects to Site Manager for tracking and control.

2. [Site Manager Module](https://www.drupal.org/project/site_manager)- https://www.drupal.org/project/site_manager

   Provides a CMS-like experience for monitoring and managing multiple sites.

3. [Operations](https://www.drupal.org/project/operations_ui) - https://www.drupal.org/project/operations_ui

   Provides a central dashboard for browsing Sites, Servers, Tasks & Users. Right now, it doesn't do much other than add an Admin menu section.

### Drupal Distributions

Since there are many possibilities with Ox, the project will contain different distributions for different purposes. Additions are welcome!

1. [Stock Ox](https://www.drupal.org/project/ox_stock)

   Stock installation of the Ox Platform. Enables Site Manager & Operations out of the box. Used for development.

### PHP Packages

Whenever a useful tool is created, a PHP Package can be created to release independently of Ox. [Drupal core uses this method](https://git.drupalcode.org/project/drupal/-/tree/11.x/composer/Plugin).

The following PHP Packages are developed in this repository:

1. [Git Split](src/composer/Plugin/GitSplit)

   A composer plugin that pushes subfolders into other git repositories using "git-split" method. Add `composer.json:extras.git-split` configuration and use `composer git:split` to push branches and tags.

2. [Drush Behat Params](drush/Commands/contrib/drush-behat-params)

    A basic drush plugin that calls behat with BEHAT_PARAMS with URL, root and drush alias set automatically.

3. [Drupal Settings](src/composer/Plugin/DrupalSettings)

    A universal Settings.php file that sets recommended defaults based on the hosting environment. Removes the need for complex settings.php files or manually setting database configuration.


## Issues

Issue management takes place in the Operations project issue queues: https://www.drupal.org/project/issues/operations?categories=All


## Development

The primary branch of development is `1.x`. 

The release branch is currently `1.10.x`. 

Everything needed to develop Operations is in this repository, including a Lando development environment with multiple sites for testing Site Manager connections:

1. Find or Submit an issue to work on at https://www.drupal.org/project/issues/operations?categories=All

1. Create an issue fork: On the issue page, underneath the issue description, there is a big green button labelled "Create Issue Fork".

2. Clone your repository and create a branch for the issue:

    See the buttons on the issue.
    
3. Enter the code and launch lando:

        lando start

4. Install Drupal Operations using the composer command.

        composer ox:launch

This will give you 4 sites to work with.

5. Do the work, push the code.
6. Once ready, submit a Merge Request using the gitlab or the issue page.
7. Mark the issue Needs review.

