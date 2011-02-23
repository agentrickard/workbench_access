/**
 * @file
 * README file for Workbench Access.
 */

Workbench Access
A pluggable, hierarachical editorial access control system

CONTENTS
--------

1.  Introduction
1.1   Use-case
1.2   Examples
1.2.1  Extending a section
1.2.2  Ignoring sections
1.3   Terminology
1.4   Managing editorial sections
1.5   Access control notes
2.  Installation
2.1   Advanced installation options
3.  Permissions
4.  Configuration
4.1   Access schemes
4.2   Access sections
4.3   Assigning editors to sections
4.4   Assigning roles to sections
5.  Using the module
5.1   Assigning nodes to sections
5.2   Viewing assigned content
6.  Troubleshooting
7.  Developer notes
7.1   API documentation
7.2   Database schema
7.3   Views integration
8.  Feature roadmap


----
1.  Introduction

Workbench Access creates editorial access controls based on hierarchies. It is
an extensible system that supports structures created by other Drupal modules.

When creating and editing content, users will be asked to place the content in
an editorial section. Other users within that section or its parents will be
able to edit the content.

A user may be granted editorial rights to a section specific to his account or
by his assigned role on the site.

As of this writing, the module support Taxonomy and Menu modules for the
management of access hierarchies.

Note that the module only controls access to content editing. It does not
provide any content filtering of access restrictions for users trying to view
that content.

----
1.2  Use-case

The above description is abstract, so let's look at a practical use-case.

Imagine that you work for a large university. The university is divided as
follows:

  -- The University
    -- Colleges
      -- College of Arts and Sciences
        -- Art
        -- Biology
        -- Physics
      -- School of Medicine
        -- Dentistry
        -- Medicine
        -- Nursing
    -- Staff
      -- Administration
      -- Faculty
      -- Support Staff
    -- Students
      -- Prospective Students
      -- Current Students
      -- Alumni
      
In such a system, people who are part of the Biology department have no
authority inside the Nursing group. The two groups are separate parts of the
hierarchy. The chair of the Biology department, therefore, cannot set policy for
the Nursing school.

Biology and Art, however, are both sub-groups of the College of Arts and
Sciences. While the chair of Biology cannot set policy for the Art department,
the Dean of the College of Arts and Sciences can set policy for both
departments.

For websites, this concept of authority often affects who can create and edit
content within different areas of a large site. Workbench Access provides a
flexible tool for defining and managing these rules.


----
1.2  Examples

In the above scenario, The University is the root element of the hierarchy. All
other elements are "children" of this "parent" item. Individual items can
themselves have children.

For our University, the following relationship exists:

  - Alumni is a child of Students
  - Students are a child of The University

When we grant access rights for content editors, we can therefore decide if a
user should be able to edit any of the following:

  -- All content in The University and all its children
  -- All content in Students and all its children
  -- Only content in Alumni

In practice, this means that the Dean can have wide authority over that part of
the website that she is responsible for, while a student intern might have very
limited roles.

In our Unversity, we have three types of web site users:

  - Editors are responsible for the entire site.
  - Deans responsible for an entire College.
  - Writers are responsible for specific departments.

In this scenario, Workbench Access would be configured as follows:

  -- Jane Doe, site editor
    -- Assigned to The University section.
    -- Can edit content on the entire site.

  -- John Smith, Dean of Medicine
    -- Assigned to the School of Medicine section.
      -- Can edit content in the following sections:
        -- School of Medicine
          -- Dentistry
          -- Medicine
          -- Nursing

  -- Ken Johnson, Alumni relations director
    -- Assigned to the Alumni section.
      -- Can edit content in the Alumni section.
      
  -- Paula Thompson, Dental school administrator
    -- Assigned to the Dentistry section.
      -- Can edit content in the Dentistry section.
      
What makes this system powerful is the inheritance of permissions based on the organizational hierarchy.  Put another way, you will see that:

  -- Jane Doe
    -- Can edit all content, including that posted by:
      -- John Smith
      -- Ken Johnson
      -- Paula Thompson
      
  -- John Smith
    -- Can edit School of Medicine content, including that posted by:
      -- Paula Thompson
      
  -- Ken Johnson, Alumni relations director
    -- Can edit content in the Alumni section.
      
  -- Paula Thompson, Dental school administrator
    -- Can edit content in the Dentistry section.


----
1.2.1 Extending a section

Let's say that Paula Thompson hires two writers for the Dental school. Those
writers can be assigned to the Denistry section as well, so that Paula can edit
their content.  We can even use Drupal's tools to extend the Dentistry section
as follows:

  -- Dentistry
    -- Courses
    -- Faculty
    -- Policies
      -- Regulatory ompliance
      -- University regulations
      
Suppose, then, that one of our new hires is Pete Peterson, an expert in
regulatory compliance. Pete can be assigned to work on just that section of the
site.


----
1.2.2  Ignoring sections

By default, all elements of a hierarchy are set as editorial sections. But it
may be that your orginization doesn't need the full complexity. Perhaps your
hierarchy can stop at the Students level.

For this case, Workbench Access allows you to disable select terms within the
hierarchy, so that not all options need to be considered when assigning
editorial access.

A simplified editorial structure for our University might look like so:

  -- The University
    -- Colleges
      -- College of Arts and Sciences
        -- Art
        -- Biology
        -- Physics
      -- School of Medicine
    -- Staff
    -- Students
      -- Alumni

In this case, the 'Prospective Students' section would simply fall under the
'Students' area. We retain Alumni as a special case, since that section has
distinct editorial needs.

This 'partial hierarchy' system is very useful when you use the hierarchy for
one purpose -- like site navigation or information architecture -- but don't
need the same complexity for editorial access.

But don't panic. You don't have to use this feature if you don't need it.


----
1.3   Terminology

Throughout this documentation and when using the module, you will run across
terms that have special meaning. This brief glossary tries to explain those
terms.

  -- user
      A site visitor who may have specific editorial privileges. If the user has
      these editorial privileges, she is referred to as an editor.

  -- user roles
      Drupal's method for grouping permissions assigned to an entire class of
      users.

  -- section
      One or more definitions that can be used to tag content for use by
      specific editorial groups. A section defines the editorial assignments
      that can be given to individual users or to entire user roles.

  -- access scheme
      A rule set used to define and control section definitions. A taxonomy
      access scheme, for instance, uses sections defined by the core Taxonomy
      module.

  -- editors
      Individual members assigned to an editorial section.
      
  -- roles
      User roles assigned to an editorial section.

If you find part of the user interface violating these definitions, please file
a bug report at http://drupal.org/project/workbench.


----
1.4  Managing editorial sections

Creating and assigning editorial access is a three-stage process. Simply put:

  1) Define the access scheme your site will use.

  2) Designate the active editorial sections you site will use.
  
  3) Assign editors to the appropriate sections.

We will discuss this process in more detail throughout this document.

The section structure itself is always controlled by another module -- as we
mentioned, both Taxonomy and Menu modules are supported. Since Drupal already
has tools for managing hierarchies, it would be wasteful to create another.
Instead, we try to use existing site concepts and workflow to enhance your
editorial options.


----
1.5   Access control notes

For those of you familiar with Drupal, we should point out that Workbench Access
is not a Node Access module. That is, it will not restrict what content users
can view on the site.

Instead, Workbench Access targets the content creators and administrators,
giving them a tool for organizing editorial responsibilities.

If you need to restrict the ability to view content, you may wish to consider
another module, such as Organic Groups (http://drupal.org/project/og) or
Domain Access (http://drupal.org/project/domain).

You could also write an extension module that handles this for us. If that
interests you, see the Developer Notes section of this document.


----
2.  Installation

Install the module and enable it according to Drupal standards.

When you install the module, it will create a test access scheme for you. This
scheme is called 'Workbench Access' and it is created as a Taxonomy vocabulary.

You should be able to view the structure at the path:

    Admin > Structure > Taxonomy > Workbench Access

You may use this to build your access hierarchy if you wish.  Simply edit the
term names to reflect the real use-case for your site.

The created hierarchy mimics a Museum web site, divided into three sections, each of which has child sections for Staff and Visitor pages:

  -- Museum
    -- Exhibits
      -- Staff
      -- Visitors
    -- Library
      -- Staff
      -- Visitors
    -- Gift Shop
      -- Staff
      -- Visitors

All existing site content will be assigned to the top-level section.

By default, user 1 (the administrative super-user) is assigned to the top-level
section, giving this account access to edit all content.

Note that when you install the module, users who are not assigned to an
editorial section may no longer be able to create or edit content. This is
normal. Since Workbench Access now controls who can create and edit content, you
will need to configure the module before resuming normal site operations.


----
2.1  Advanced installation options

You may disable this installation behavior by adding the following line to
settings.php before you install the module.

  $conf['workbench_access_install_minimal'] = 1;

If you do so, you must manually configure the module before resuming normal
content editing, since users may not have any editorial rights.
