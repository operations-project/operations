# Drupal Ox
## The Operations Experience Platform

Welcome to Drupal Ox: the Ops Dashboard built in Drupal.

This repository serves as the main development codebase for all of the Operations submodules and packages.

For more information, see the [Drupal.org Project Page](https://www.drupal.org/project/ox).

## Components

### Drupal Modules

Whenever possible, the Drupal Operations Modules can be installed independently of one another. The Site module is designed to go into every site, so that it can report back to Site Manager.

1. [Site Entity Module](/src/modules/site) - https://www.drupal.org/project/site

   Provides advanced status and information about any Drupal site. Connects to Site Manager for tracking and control.

2. [Site Manager Module](/src/modules/site_manager)- https://www.drupal.org/project/site_manager

   Provides a CMS-like experience for monitoring and managing multiple sites.

3. [Operations](/src/modules/operations) - https://www.drupal.org/project/operations

   Provides a central dashboard for browsing Sites, Servers, Tasks & Users. Right now, it doesn't do much other than add an Admin menu section.

### Drupal Distributions

Since there are many possibilities with Ox, the project could contain different distributions for different purposes. Additions are welcome!

1. [Stock Ox](src/profiles/ox_stock)

   Stock installation of the Ox Platform. Enables Site Manager & Operations out of the box. Used for development.

### PHP Packages

Whenever a useful tool is created, a PHP Package can be created to release independently of Ox. [Drupal core uses this method](https://git.drupalcode.org/project/drupal/-/tree/11.x/composer/Plugin).

The following PHP Packages are developed in this repository:

1. [Git Split](composer/Plugin/GitSplit)

   A composer plugin that pushes subfolders into other git repositories using "git-split" method. Add `composer.json:extras.git-split` configuration and use `composer git:split` to push branches and tags.

## Issues

Issue management takes place in the Ox project issue queues: https://www.drupal.org/project/issues/ox?categories=All


## Development

The primary branch of development is `1.x`. 

The release branch is currently `1.10.x`. 

Everything needed to develop Ox is in this repository, including a Lando development environment with multiple sites for testing Site Manager connections:

1. Find or Submit an issue to work on at https://www.drupal.org/project/issues/ox?categories=All

1. Create an issue fork: On the issue page, underneath the issue description, there is a big green button labelled "Create Issue Fork".

2. Clone your repository and create a branch for the issue:

        # Replace the number with issue ID        
        git clone git@git.drupal.org:project/ox-12345678.git
        git checkout -b 12345678-fix-thing
        git push -u origin 12345678-fix-thing
    
3. Enter the directory and launch lando:

        cd ox
        lando start

4. Install Drupal Ox using Drush or the Web UI.

        drush site:install

5. Do the work, push the code.
6. Once ready, submit a Merge Request using the gitlab or the issue page.
7. Mark the issue Needs review.

