# Site Module

The **Site.module** gives you extra information about your Drupal site by providing a SiteEntity that stores site state and other information over time.

The `SiteEntity` has a "state" property, an extensible way for specifying the overall health of a Drupal site. Support for Drupal's Status report and Site Audit is built in.

The `SiteEntity` is Fieldable, can store Config API data, State API data, or arbitrary data with revisions, providing a historical record of whatever data points are desired.

The `SiteEntity` can then be sent to another Drupal site running Site Manager, or to any REST endpoint.

When used with the Site Manager module, multiple Site Entities can be stored, giving Drupal the ability to act as a site monitoring and control dashboard.

## Usage

1. Enable "Site" module via "Admin > Extend" or with `drush en site`.
2. Visit **Admin > Reports > Site Status**.
3. In this section:
  - **Status:** This page displays the current state of your site using the `SiteDefinition` config entity.
    - *Current Status*: Displays Site State, the Reason for the state, metadata, and current Config API and State API data.
    - *Save Site Report:* This form is used to save a snapshot of the `SiteDefinition` into a `SiteEntity`.
    - *Status History* displays a list of Site reports. Each entry has it's own page. Click the "Site Title" links to view.
  - **Settings:** Edit the `SiteDefinition` config entity here. 
    - *State:* Choose which factors to use when calculating the State of the site.
    - *Site Reporting*: Configure how Site data is handled. Set interval for saving and sending site data. Configure Destinations for sending Site data  via REST. 
    - *Site config:* The `SiteDefinition` entity can load additional data from Config API and States API into the `SiteDefinition::data` property. Use **Configuration items to load.** and **State Items to Load** to define what data is included in the `SiteDefinition` (and saved to `SiteEntity::data`).
    - *Site Overrides:* Provides a mechanism to allow Config and States to be overridden by data in the `SiteEntity`. Enter the names of the Config API and State API items that are allowed to be overridden.
      When saving the `SiteEntity`, the data in `SiteEntity::config_overrides` and `SiteEntity::state_overrides` will be set automatically, if allowed.
  - **Edit Info:** This is the Edit page for this site's `SiteEntity` (a revisionable content entity). Store metadata like "Description", or add your own fields.
  - **Manage fields**, **Manage display**, **Manage Form**: If Field UI module is enabled, you will see the standard Field Management pages here. Add your own fields and control how `SiteEntity` is displayed.

## Config Entity `SiteDefinition`

Each site get's a default `SiteDefinition` config entity called `self`.

The `SiteDefinition` Config Entity represents the current state of the site. It contains some editable properties and some are generated from site data.

See `site.site_definition.self` config entity defaults here: [./config/install/site.site_definition.self.yml](./config/install/site.site_definition.self.yml).

```yaml
id: self

# Editable Properties
canonical_url: ""
git_remote: ""
description: "A site definition representing this site."

# List of config items to load into a Site Entity.
configs_load:
  - core.extension
  - system.site

# List of config items to allow loading from remote site entity.
configs_allow_override: []

# List of state items to load into a Site Entity.
states_load:
  - install_time
  - system.cron_last
  - system.maintenance_mode

# List of state items to allow loading from remote site entity.
states_allow_override: []

# List of factors that affect state
state_factors:
  - system

# Arbitrary data
data: {}
settings: {}
```

To load the `SiteDefinition` entity for the current site, load the entity with id "self":

```php
$site = \Drupal\site\Entity\SiteDefinition::load('self');

if ($site->state == \Drupal\site\Entity\SiteDefinition::SITE_ERROR) {
  \Drupal::messenger()->addError($site->reason);
}
```

### Saving `SiteEntity` from `SiteDefinition`

The `SiteDefinition` class has a simple method for converting itself into a `SiteEntity` content entity:

```php
# Get a `SiteEntity` object, alter and save it.
$site_content_entity = SiteDefinition::load('self')->toEntity();

$site_content_entity->revision_log = "Programmaticly created Site Entity";
$site_content_entity->setNewRevision(TRUE);
$site_content_entity->save();

# Save SiteEntity, automatically saving a new revision.
SiteDefinition::load('self')->saveEntity();
```

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


