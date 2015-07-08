# Behat tests

This directory contains Behat tests that cover the module.

## Running tests

Before you can run the tests you'll need the drupal-extension for Behat.
To get that, you can run

`composer install`

from within this directory.

You can then run

`./vendor/bin/behat`

This command will run all Behat features within the features directory.

### Composer bike-shedding

In practice, developing Workbench modules means installing the drupal-extension for Behat once per module.
That's not a good answer long term.
Here is a Redmine ticket to revisit Composer and directory structure. https://redmine.palantir.net/issues/44108

## Adding tests

When working on a user story lean toward making a new .feature file in the features directory instead of adding on to existing files.
