Drupal Operations
=================

Changelog
---------


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

