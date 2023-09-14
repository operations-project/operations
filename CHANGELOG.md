Drupal Operations
=================

Changelog
---------

# 2.0.0-beta4
## September 14, 2023

https://www.drupal.org/project/site/releases/2.0.0-beta4

- New Project UI:
  - List page with projects and environments with "Add Project" button.
  - Canonical Project page with environments and "Add Site" button.
  - Added "Canonical URL" to Drupal Project entities with display on the site that matches.
  - Project display on About this site page.
  - Breadcrumbs from Projects to environments.
  - Display Composer.json metadata on site widget. Links to support!
  - "Widget" display mode for sites.
- New Properties:
  - Site Root: Path to git repo root.
  - Composer Json: The entire composer.json file contents.
  - Drupal Environment: The DRUPAL_ENV environment variable.

# 2.0.0-beta3
## September 1, 2023

https://www.drupal.org/project/site/releases/2.0.0-beta3

- Fix Site Manager Receive data, update bundle field metadata.
- Fix timestamp display on site manager when requesting data.
- Provide a link to edit Site API key if access denied.
- Fix field, state, reasons sending & overriding.
- Fix perpetual warning.
- Remove SiteRemote service.

# 2.0.0-beta2
## August 24, 2023

UPDATE: Using drupal:jsonapi in dependencies so we can actually install.

https://www.drupal.org/project/site/releases/2.0.0-beta2

# 2.0.0-beta1
## August 24, 2023

https://www.drupal.org/project/site/releases/2.0.0-beta1

First beta of the new 2.x branch.
NOTE: There is NO UPGRADE path from drupal/site-site 1.x.

The 1.x versions were mistakenly called `drupal/site-site`. If you installed this version, remember to:

1.  Uninstall Site module BEFORE removing the code from your codebase.
2.  You must remove the old package because it has a different name: composer remove drupal/site-site

Operations 2!

This is a major change to the architecture of Operations 1, a new version is warrented.

- Brand new UI: Site teasers show all information in easy to read display.
- Create Site Entities from ANY URL. Add google.com or drupal.org.
- Site Manager homepage: List of site teasers.
- Views support.
- Better UX for setting up site.module: Add drupal site, if no SiteAPI is found, instructions appear.
- Checks HTTP status on all URLs.
- Extract fields from headers and content where possible, such as PHP Version, Drupal version, Site Title and Host provider.
- Site Type bundle: Website, Drupal Website, Site Manager.
- Bundle classes with inheritance: DrupalSiteBundle extends PHPSiteBundle extends SiteBundle.
- `SiteProperty` plugins can now define the bundle classes they are attached to, making it easy to attach property fields to multiple Site Types.
- Add Site Manager sites to send data to multiple Site Managers.
- `SiteEntity` is now for any website.
- `DrupalSite` entity is now the entity with Drupal Site UUID as ID. `SiteEntity::drupal_site` field is a reference to `DrupalSite` DrupalSite entities get created automatically. DrupalSite entity page shows all sites for that Drupalsite.
- Removed SiteDefinition config entity.
- More automated behat tests.
- New `SiteAction` plugin with first implementation: User Login. Remotely log into a site!
    - SiteActions create a new link in the "Operations" dropdowns, and work locally or remotely. See `SiteActionsBasePlugin`.
- New Properties:
    - HTTP Status, generated from list of SiteURLs.
    - Site Title, generated from HTML title.
    - Drupal Site Name.
    - Last Cron run.
    - Site Installation time.
    - Site Install profile.
- FieldFormatter widget for State & Reasons.
- JSON:API. Site Module now uses JSON:API for posting and getting data.
- Added "ox_stock" install profile for basic site manager install.

### 2.x 

SiteEntity is now any website. It has a reference field to DrupalProject via it's ID, the Drupal Site UUID.

DrupalProject is now the entity with the site UUID as entity ID.

More info to come. I just thought I needed to get this out to the people.

### 1.x

  Everything in 1.x was fast and furious. 1.x was essentially the R&D Phase.

  It worked, and lessons were learned. 2.x was designed out of those learnings.

  You can only add a site by setting up site.module as a client.
  Site entities required a Drupal Site UUID as an ID. This severely limited the ability to expand the system to track individual sites/environments.

  That's all I will put for now. It's time to get a real alpha out.

  Peace.

