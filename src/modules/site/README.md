# Site Entity Module

The **Site.Module** provides an entity to represent any website.

Site Module can view, store, and send data about your Drupal site. When enabled, a *Site Entity* is created that includes data on Drupal & PHP versions, Git information, and more.

The *Site Entity* has a "Site State" property indicating the overall health of your site using the core "Status report", [Site Audit module](https://www.drupal.org/project/site_audit) reports, or write your own SiteState plugin. The "Reason" property stores text describing why the site is in a certain state.

The HTTP status code for the site affects state, making Site module a lightweight monitoring solution.

The *Site Entity* is revisionable, providing a detailed history of the state of your site, including changes to configuration with a log of who changed what, where.

The *Site Entity* is fieldable, allowing you to add fields to your website.

The *Site Entity* has a REST API, allowing you to POST or GET site entities from one site to another or to third-party systems, such as the [Site Manager module](https://www.drupal.org/project/site_manager).


## Drupal Operations Experience Platform

This project is a part of the [Drupal Operations Platform](https://www.drupal.org/project/operations), the Ops Dashboard built in Drupal.

For all issues, development, and more information, see https://www.drupal.org/project/operations

## How to use

Install site.module with composer & drush:

        composer require drupal/site
        drush en site

*NOTE:* If you are upgrading from Site Module 1.x, the package name is `drupal/site-site`. You MUST uninstall site module before removing the old version and adding the new. See [upgrading from 1.x](./)

### Site Status Widget

When enabled, you will see an indicator at the top right of the Admin Toolbar showing "OK", "Warning", or "Error". Click that indicator to view the "Site Status" page.

### Site Status Page

On the far upper left corner of the Admin Toolbar, Click "Manage", then hover over the "Drupal Logo" menu to see "Site Status" and sub pages:

- **Status:** View current Site State, reasons, & properties. Click the "+Save Report" button to save a snapshot of this data.
- **History:** View Site Status snapshots.
  - This is the Site Entity Revisions page. New snapshots are made when config changes or on cron.
  - Click the site title links to view the data from that snapshot.
- **Settings:** Configure how Site.Module behaves.
  - *State:* Allows you to control what factors affect Site State. Built in state handlers include Drupal core "Status report" and Site Audit reports.
  - *Site Reporting:* Control when snapshots are saved or sent. Set a "Site Data Destination" to post updates to another site via REST.
  - *Site Config:* Configure what Drupal Configuration items and Drupal State values are stored in the Site Entity.
  - *Site Overrides:* Allow a remote site data receiver to override configuration or fields. Choose what fields, configurations, or states will be set from the remote site entity.
- **Edit Info:** The Site Entity Edit page, with fields, revision log, and standard entity form features.
- **Fields:** Manage fields, form, and display of the Site Entity.

## Drush

There are drush commands for setting the state and reason for a site.

This is useful for using your own scripts to determine state.

### Commands

#### `drush site:state`

Show the current state of the site.

#### `drush site:state all`

Show the state of all sites.

#### `drush site:state --state=ok`

Set the state of the site.

#### `drush site:state --state=warning --reason='Something failed'`

Set the site state to "warning" and save the reason text "Something failed".

### Automation

The drush command `site:state` can set the state of any SiteEntity, along with reasons text and revision log.

This can be used to set site state during a script.

At the start of your tests, you can set the state to "processing":

        drush site:state --state=processing --revision-log='Tests begun.' --yes

At the end of the test, you can set state based on the result of your script by using bash && and ||.

        bin/behat \
          && drush site:state --state=ok --revision-log='Behat tests passed!' --yes \
          || drush site:state --state=error --revision-log='Behat tests failed!' --yes"

In this example, the `bin/behat` command will run, and if successful, the command after `&&` will run (State: ok).

If not successful, the command after `||` will run (State: error).

### Redirecting output

Set reason to the output of a command.

        drush @operations site:state --state=ok --reason="$(drush status)"

NOTE: I can't seem to get piping to work. Can anyone help?

        # DOES NOT WORK
        bin/behat | drush @operations site:state --state=ok --reason=


## Side Definition Entity

### Dynamic Properties

- State
- Reason
- Site UUID
- Site Title
- Site URI

### Editable properties

- Description
- Canonical URI
- Git Remote
- Config Items: List of config items to include in the Entity data
- Allowed Remote Configs: List of config items to allow changing by Remote Site Manager. *Coming Soon*..

## Dynamic Property: State

The `SiteDefinition->state` indicates the overall health of the website. Possible states are OK, INFO, WARN, and ERROR.

Any module can affect the `state` of the entity by implementing an `EventSubscriber` for `site_get_state`.

On the "Admin > Config > Advanced Site Settings" page, there are checkboxes for "State Factors".

Check "Status Report" to use Drupal core's Status Report Page as the Site State indicator.

If you have Site Audit module installed, you can select Site Audit Reports to be a factor in site state.

## Dynamic Propert: Reason

A string with information about why the site is in a certain state.

## Editable Property: Config Items

A list of configuration items that should be loaded into the `SiteDefinition::config` property.

This will load the site's active config into the Site Entity for quick retrieval.

To choose what configs to load, simply visit **Admin > Config > Advanced site settings** and fill in the

## Editable Property: Allowed Remote Configs (Coming Soon)

A list of configuration items that a site will allow to be updated from a remote Site Manager site.

If posting Site data to a Site Manager site, the reponse can contain configurations that will be automatically set.


## Environment Variables

Some properties can be set by setting environment variables because they cannot be reliably derived.

Set the following env vars to set properties that can be saved:

### `DRUPAL_SITE_HOST_PROVIDER`

String indicating what host this site is on.

### `DRUPAL_SITE_GIT_REFERENCE`

The current git reference for this site.

### `DRUPAL_SITE_GIT_REMOTE`

The current git reference for this site.

Example:

```php

/**
 * Tell site.module we are hosted on Acquia
 */
if ((bool) getenv('AH_ENVIRONMENT')) {
  putenv('DRUPAL_SITE_HOSTING_ENVIRONMENT=acquia');
}

```

