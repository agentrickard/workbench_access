@api
Feature: Node Type pages
  As an administrator,
  I want node type forms
  So that I can enable Workbench Access for a content type


  Scenario: Can't see "Enable" Checkbox without permission
    Given I am logged in as a user with the "access administration pages,administer content types" permissions
    When I visit "admin/structure/types/manage/page"
    Then I should not see "Enable Workbench Access control for Basic page content."

  Scenario: Can see "Enable" Checkbox with permission
    Given I am logged in as a user with the "access administration pages,administer content types,administer workbench access" permissions
    When I visit "admin/structure/types/manage/page"
    Then I should see "Enable Workbench Access control for Basic page content."

  Scenario: Node type settings visible to admins
    Given I am logged in as a user with the "administrator" role
    When I visit "admin/structure/types/manage/page"
    Then I should see "Enable Workbench Access control for Basic page content."

  Scenario: Workbench Access is off by default for pages
    Given I am logged in as a user with the "administrator" role
    When I visit "admin/structure/types/manage/page"
    Then the checkbox "workbench_access_status" should not be checked

  Scenario: Saving the enabling of Workbench Access for a content type
    Given I am logged in as a user with the "administrator" role
    When I visit "admin/structure/types/manage/page"
    Then the checkbox "workbench_access_status" should not be checked
    And I check the box "workbench_access_status"
    And I press the "Save content type" button
    When I visit "admin/structure/types/manage/page"
    Then the checkbox "workbench_access_status" should be checked

  Scenario: Saving the disabling of Workbench Access for a content type
    Given I am logged in as a user with the "administrator" role
    When I visit "admin/structure/types/manage/page"
    Then the checkbox "workbench_access_status" should be checked
    And I uncheck the box "workbench_access_status"
    And I press the "Save content type" button
    When I visit "admin/structure/types/manage/page"
    Then the checkbox "workbench_access_status" should not be checked
