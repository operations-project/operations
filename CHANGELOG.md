Drupal Operations
=================

Changelog
---------

# 2.1.0-beta1
## October 12, 2023

Operations 2.1.x: Project Entities

- Created project and project type entities.
- Moved functionality around `drupal_project` entity type to new `project` entity type, with `drupal` bundle.
- Created `project` field on all sites so all sites can be grouped.
- Refactored views, view modes, etc for new entity.
- Created bundle classes for projects: WebProject includes URL, CodeProject includes Git Remote, Drupal project includes Site UUID.
- Added upgrade tests.

This release changes entity structure, so we bumped the version to 2.1.0.

# 2.0.0-beta13
## September 29, 2023

- Redesign Project Create form: If canon URL is entered, site will be created and Project name automatically created from HTML title.
- Redesign Site History and Site List page to offer detail dropdown when clicked.
- Fix bug breaking site entity creation on sites where new entity is created for an existing site.
- Fix warnings when refreshing reports.
- Fix warning from GitRemote plugin if there is no remote found.
- Allow bundles to override the data shown on the history tab. This allows drupal sites to show drupal version, etc.
- Add Config Changes display to Reasons output.

# 2.0.0-beta12
## September 26, 2023

Fixed error with /jsonapi/self.

# 2.0.0-beta11
## September 26, 2023

- Fixed broken Basic Website entity addition. Added test to verify.
- Moved `composer_json` property from schema field to `data` field because it was breaking basic sites. We don't need an extra field for it.
- Fix problem with dependencies causing Site Manager and site to be different major versions.
- Removed `drupal/ip_consumer_auth`. It was unnecessary and was preventing Drupal 10 installation!
- Add special config to allow multiple site entities with the same URL.
- Fixed missing "Add Site" button on Admin Sites page.
- Changed "Drupal Project" URLs to `/project/{drupal_project}`
- Check for view access to SiteEntity when requesting via SiteAPI.
- Added drush command `site:state` to view and update site states and reasons. See README.md for examples for automatically reporting site state in response to custom scripts.
- Implemented `site:state` for `composer operations:test:run` in the dev stack.
- Added `processing` state for indicating that something is happening to a site like a deploy or a test.
- Ensure revision log gets reset.

# 2.0.0-beta10
## September 22, 2023

- Reimplemented Field Overrides for drupal project and sites. Client sites can now read fields from site manager if they are set in Remote Field Overrides.
- Fixed field data sending. Drupal project and site entities now send all data and gets saved in site manager.
- Allow API key to be sent along with Drupal project entity, providing instant login from site manager.
- Fixed exception throwing and catching.
- Improve messaging when API keys are missing.
- Allow Site Manager to receive entities with fields that do not exist, so that client sites can post if they have site-entity altering behavior.
- Fix remote timestamp saving.
- Create JsonAPI trait for converting to and from jsonapi arrays.
- Update Drupal project data when saving site.
- Fix sending reports on cache clear.
- Enable instant connections in lando for testing using settings.php.
- Cleaned up composer development commands.
- Added drush command `key:set` to set a user's api key for key_auth module.
- Added drush command `site:state` to view and update site states and reasons. See README.md for examples for automatically reporting site state in response to custom scripts.
- Implemented `site:state` for `composer operations:test:run` in the dev stack.
- Added `processing` state for indicating that something is happening to a site like a deploy or a test.
- Ensure revision log gets reset.

# 2.0.0-beta8
## September 20, 2023

- Fix Node editing breaking because of bad hook_help() implementation.

# 2.0.0-beta7
## September 19, 2023

- Add update hook API key on Drupal Project entities.

# 2.0.0-beta6
## September 19, 2023

- Add update hook for new fields to help all 6 of our current users.
- Export config for displaying drupal_env, site_root, and composer_json fields.
- Add save_on_cache_rebuild feature.
- Added Project "operations" links dropdown.
- Finished refactoring for SiteSelf service: use saveEntity() not saveRevision().
- Fix SaveEntity() to validate before save.
- Allow DrupalProject's to store API key so it uses the same key for all sites.
- Save canonical url from the first site entities URL.
- Fix "Add Field" button in About this site page.
- Fix Drush Behat Params bin dir discrepencies.
- Gorgeous test failure output and last page output artifact saving in gitlab CI.

# 2.0.0-beta5
## September 14, 2023

https://www.drupal.org/project/site/releases/2.0.0-beta5

- Don't break if no composer.json links are present.

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

