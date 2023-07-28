# Site Manager

## Track & Control Drupal Sites

This is the documentation for Site Manager Module.

Site Manager is a content manager where your content is websites.

It can be used to build Dashboards for keeping track of your websites.

Site Module connects Drupal sites to Site Manager, sending the entire Site 
Entity over via REST.

Site Manager can control the config, state, and fields of connected sites.

## Usage

Site Manager provides a content entity called "Site". It is basically a CMS 
for sites.

### Admin > Content > Sites

The list of sites is under Admin > Content > Sites.

### Add Site

Not Yet Implemented.

Currently, the only way to add a site is to install Site.module on the 
client site and configure it to start sending it to Site Manager.

Adding a Site Entity directly using this form is challenging because we need 
an ID: The Site UUID.

@TODO:

1. Create the "Add site" route to show 2 options for adding sites: instructions 
   for adding `drupal/site` to a client site, and the "Add Site" form. 
2. Generate a site UUID by default, but allow a user to override it if they 
   know it.
2. Once saved, on the site entity, show the user how to connect a remote 
   site by setting the UUID and adding `drupal/site`.


### Admin > Structure > Site Types

The list of site types. There is only one site type right now: "default". 

This is where you can manage the fields of Site Entities.

Site Types are not yet implenented.

### Remote Control

Visit Admin > Content > Site Manager to setup site controls.

Site manager can control remote sites by overriding Drupal config, state
values, or fields.

To set global config or state values, visit the Site Manager Settings page.

### Overriding Config and State

Every Site entity in Site Manager has two properties, `config_overrides` and 
`state_overrides`.

The global Site Manager configs are loaded into these properties.

Entity API hooks can alter these properties as well to override things on a 
per site basis.

For example, the Site Entity could have a field with options on site manager,
with custom  code translating that to drupal config Yaml.

### Overriding Site Entity Fields

Fields can be added to Site Entities in Site Manager and client sites. If 
they match, the data can be sent from one to another.

By default, client sites send their Site entity to Site Manager, so field data 
gets sent with it. The Site Manager entity gets saved based on the received 
data.

If you want client sites to receive field data from Site Manager, you can 
add the fields to "Remote Fields" in the client site's Site.module settings. 

This will tell the client site to update it's copy of it's own site entity 
with field data received from Site Manager.

## Site Actions

### Get Login Link

Site Manager can connect to client sites and request one-time login links.

To set up remote login, there are a few steps:

1. On the client site, get an API key for the user you wish to login with

