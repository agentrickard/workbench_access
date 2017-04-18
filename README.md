# Workbench Access

The Workbench Access module creates editorial access controls based on hierarchies. It is an extensible system that supports structures created by other Drupal modules.

When creating and editing content, users will be asked to place the content in an editorial section. Other users within that section or its parents will be able to edit the content.

A user may be granted editorial rights to a section specific to his account or by her assigned role on the site. To create, edit and delete content in a section, the user must have the core node module permission (e.g. `Edit all Article content`) and the content must be assigned to the same section.

As of this writing, the module supports the core Taxonomy and Menu modules for the management of access hierarchies. It uses Drupal 8's plugin system to create new hierarchies.

Note that the module only controls access to content editing. It does not provide any content filtering of access restrictions for users trying to view that content.

While Workbench Access is part of a larger module suite, it may be run as a stand-alone module with no dependencies.

## Installation and Configuration

### Install
To start using the module, install normally and then go to the configuration page at `admin/config/workflow/workbench_access`. From there, select the access control scheme you wish to use (by default, either Menu or Taxonomy) and the corresponding hierarchies that you wish to use for access control.

Tip: It is best if you create your hierarchy (say a Taxonomy Vocabulary called `Editorial section` before configuring the module.

If you want to test how the system works, you can run the drush command `drush wa-test` to install and configure a sample taxonomy hierarchy.

### Configure
After assigning the access control scheme, you must save the form. After saving, you will be asked to select the fields you wish to use for access control. You may elect not to place a content type under access control, but to use Workbench Access with a content type, you must first create and select a field that contains the data that will be used to assign access.

Note that the Menu access scheme only supports one field type, the default menu selection field.

### Assign
Once you select the fields, it is time to assign users to editorial sections. For each role that should use Workbench Access, give the role either of the following permissions:

* Bypass Workbench Access permissions
  This permission assigns users in the role to all sections automatically. Give only to trusted administrators.
* Allow all members of this role to be assigned to Workbench Access sections
  This permission lets users and roles be assigned to specific editorial sections. It is the default permission for most roles.

After permissions are assigned, go to the Sections overview page `admin/config/workflow/workbench_access/sections`. This page shows a list of all sections in your access hierarchy and provides links for adding roles or users to those sections.

Note that when granting access, the hierarchy is enforced such that if you have the following structure:

```
- Alumni
-- Events
-- Giving
```

A user or role assigned to `Alumni` will also have access to `Events` and `Giving` and does not need to be assigned to all three.
## Contributing

If you'd like to contribute, please do. Github forks and pull requests are preferable. If you prefer a patch-based workflow, you can attach patches to GitHub issues or Drupal.org
issues. If you open a Drupal.org issue, please link to it from the appropriate GitHub issue.

The GitHub issues are grouped under three milestones:

1. Alpha -- items required for a test release. When this is complete, we will roll an alpha1 release on Drupal.org.
2. Beta -- items considered critical features for a release. When complete, we will roll a beta release on Drupal.org.
3. Final -- items required for a stable, secure release on Drupal.org.

We would like to tackle issues in that order, but feel free to work on what motivates you.

## Testing

The module does not have solid test coverage, and complete coverage is required for release. Right now, we mostly use SimpleTest, because it is most familiar, but unit tests are welcome.

All pull requests will automatically run tests in TravisCI. Test coverage runs against the following:

1. PHP 5.5, 5.6, and 7.
2. Drupal 8.0.x and 8.1.x.
3. Tests also run for the HHVM but are allowed to fail.

[![Build Status](https://travis-ci.org/agentrickard/workbench_access.svg?branch=8.x-1.x)](https://travis-ci.org/agentrickard/workbench_access)

## Developer Notes

### Access controls
Workbench Access only applies to content (node) editing.

By design, Workbench Access never _allows_ access. It only responds with `neutral` or `deny`. The intention is that normal editing permissions should apply, but only within the sections that a user is assigned to.

Access controls are controlled by the `WorkbenchAccessManager` class but the individual response is delegated to plugins via the `checkEntityAccess` method provided in the `AccessControlHierarchyBase` plugin. So if you want to change access behavior, you can write your own plugin or extend an existing one.

### Data storage
Access is granted either at the `user` or `role` level. User-level access is stored in the field `user.field_workbench_access`, which installs with the module. Role-level access is stored in the `state` system. We use the state system instead of config because access control hierarchies are typically content entities, which cannot be exported via config.

*This storage is now a service, and swappable. In the future, we will likely replace the current storage with an entity-based system.*

This means that base configuration of Workbench Access is config-exportable, but the actual access control assignments are not. This is a limitation of Drupal 8's design.

Content-level data is stored on individual fields, which must be created and assigned via the Workbench Access configuration page at `admin/config/workflow/workbench_access`.
