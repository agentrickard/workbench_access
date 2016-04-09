# Workbench Access

The Workbench Access module creates editorial access controls based on hierarchies. It is an extensible system that supports structures created by other Drupal modules.

When creating and editing content, users will be asked to place the content in an editorial section. Other users within that section or its parents will be able to edit the content.

A user may be granted editorial rights to a section specific to his account or by her assigned role on the site. To create, edit and delete content in a section, the user must have the core node module permission (e.g. `Edit all Article content`) and the content must be assigned to the same section.

As of this writing, the module supports the core Taxonomy and Menu modules for the management of access hierarchies. It uses Drupal 8's plugin system to create new hierarchies.

Note that the module only controls access to content editing. It does not provide any content filtering of access restrictions for users trying to view that content.

While Workbench Access is part of a larger module suite, it may be run as a stand-alone module with no dependencies.

Contributing
====

If you'd like to contribute, please do. Github forks and pull requests are preferable. If you prefer a patch-based workflow, you can attach patches to GitHub issues or Drupal.org
issues. If you open a Drupal.org issue, please link to it from the appropriate GitHub issue.

The GitHub issues are grouped under three milestones:

1. Alpha -- items required for a test release. When this is complete, we will roll an alpha1 release on Drupal.org.
2. Beta -- items considered critical features for a release. When complete, we will roll a beta release on Drupal.org.
3. Final -- items required for a stable, secure release on Drupal.org.

We would like to tackle issues in that order, but feel free to work on what motivates you.

Testing
====

The module does not have solid test coverage, and complete coverage is required for release. Right now, we mostly use SimpleTest, because it is most familiar, but unit tests are welcome.

All pull requests will automatically run tests in TravisCI. Test coverage runs against the following:

1. PHP 5.5, 5.6, and 7.
2. Drupal 8.0.x and 8.1.x.
3. Tests also run for the HHVM but are allowed to fail.

[![Build Status](https://travis-ci.org/agentrickard/workbench_access.svg?branch=master)](https://travis-ci.org/agentrickard/workbench_access)

Developer Notes
====

