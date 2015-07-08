# Unit Tests for Workbench access

## Writing tests

When writing tests, follow guidelines here [https://docs.google.com/document/d/1hpRKwm4UjmIF6wgCoKyJQYdQ2oxZ4IYzXemIaU36YxE/edit](https://docs.google.com/document/d/1hpRKwm4UjmIF6wgCoKyJQYdQ2oxZ4IYzXemIaU36YxE/edit)
@todo, Update this link a public docs page

Put all unit tests in the group workbench_access

## Running tests

You can use the phpunit that comes with Drupal Core.
From the the core directory run

```
core/vendor/bin/phpunit -c core   --group workbench_access
```

Or if you want to the tests to run faster (because milliseconds matter) you can restrict phpunit by directory rather
than group.

```
core/vendor/bin/phpunit -c core   modules/workbench_access/tests/
```
