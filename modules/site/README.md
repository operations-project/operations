# Site.Module

The **Site.Module** provides a new way to view and store data about your Drupal site. When enabled, a *Site Entity* is created that includes data on Drupal & PHP versions, Git information, and more. 

The *Site Entity* has a "Site State" property indicating the overall health of your site using the core "Status report", [Site Audit module](https://www.drupal.org/project/site_audit) reports, or write your own SiteState plugin. The "Reason" property stores text describing why the site is in a certain state.

The *Site Entity* is revisionable, Addproviding a detailed history of the state of your site, including changes to configuration with a log of who changed what, where.

The *Site Entity* is fieldable, allowing you to add fields *to your website*.

The *Site Entity* has a REST API, allowing you to POST or GET site entities from one site to another or to third-party systems, such as the [Site Manager module](https://www.drupal.org/project/site_audit).

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

