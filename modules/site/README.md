# Site Module

The Site Module provides a way to track your Drupal sites.

The Site Entity has properties such as Git Remote and URI, and can be extended to store additional information like [Site Audit Reports](https://drupal.org/project/site_audit).

The Site Entity data can then be sent to a remote server for tracking in a central location, or it can be saved locally
on a recurring basis to track changes over time.

The Site Entity can send arbitrary config to any REST endpoint. The endpoint can respond with `config_overrides`, 
allowing users to control config remotely.

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


