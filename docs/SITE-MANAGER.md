# Site Manager

## Track & Control Drupal Sites

This is the documentation for Site Manager Module.

Site Manager is a content manager where your content is websites.

It can be used to build Dashboards for keeping track of your websites.

Site Module connects Drupal sites to Site Manager, sending the entire Site 
Entity over via REST.

Site Manager can control the config, state, and fields of connected sites.

Site Manager can remotely sign into Drupal sites, allowing admins to connect directly to client sites without needing a password or Drush.

## Drupal Operations Experience Platform

This project is a part of the [Drupal Operations Platform](https://www.drupal.org/project/operations), the Ops Dashboard built in Drupal.

For all issues, development, and more information, see https://www.drupal.org/project/operations

## Usage

Site Manager provides a content entity called "Site". It is basically a CMS 
for sites.

### Admin > Content > Sites

The list of sites is under Admin > Content > Sites.

### Add Site

A site entity can be any website. Visit Admin > Content > Sites to view the list of sites.

Press "Add site" to create a new site.

### Site Types

There are 3 basic site types by default:

1. Basic website. Use to add any generic URL.
2. Drupal website. Use for Drupal websites that might implement Site API for extra information. Also allows site actions to be taken like User login link.
3. Site Manager. Use for Site Manager instances that you want to send site data to.

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

### Sign In

Site Manager can connect to client sites and request one-time login links.

To set up remote login, there are a few steps:

1. On the client site, visit "My Account" > "Key Authentication" for the user you wish to allow signing in as.
2. Generate and copy an API key.
3. On site manager, edit the Site entity and add the API key.
4. Click the "^" menu on the site and click "Sign in" to view the "request a login link" form.
