# Workbench Access

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
