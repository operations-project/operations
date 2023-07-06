# Site Manager

Site Manager provides a user interface for monitoring and managing Drupal sites.

It is powered by the Site Module, which is provides a content entity called "Site" that can be managed just like any other content.

Site Manager includes a REST API so other sites can send and receive information.

Site Manager allows control of remote sites via Config, field, and State overrides. Remote sites can configure what overrides are allowed. 

## Drupal Operations Experience Platform

This project is a part of the [Drupal OX Platform](https://www.drupal.org/project/ox), the Ops Dashboard built in Drupal.

For all issues, development, and more information, see https://www.drupal.org/project/ox

## How to use

Install site.module with composer & drush:

        composer require drupal/site_manager-site_manager
        drush en site_manager

*NOTE:* The package name is "site_manager-site_manager" due to a mistake in Drupal packagist packing.


