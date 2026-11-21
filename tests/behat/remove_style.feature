@editor @editor_tiny @tiny @tiny_c4lauthor @javascript
Feature: Remove the style of a component and keep what was written
  In order to reuse a component's text without its styling
  As a teacher
  I need Remove style to leave plain HTML behind, with nothing of the author's lost

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "activities" exist:
      | activity | name   | course | idnumber | intro       | introformat | content            | contentformat |
      | page     | Page 1 | C1     | page1    | Description | 1           | <p>Hello world</p> | 1             |

  Scenario: A link block becomes a plain link, not a block of clickable text
    Given I am on the "Page 1" "page activity editing" page logged in as "teacher1"
    And I click on the "C4L Author" button for the "Page content" TinyMCE editor
    And I click on "Link block" "button" in the ".tiny_c4lauthor__sidebar" "css_element"
    When I switch to "tiny_c4lauthor__inner-iframe" class iframe
    And I hover ".c4l-linkblock" "css_element"
    And ".c4lauthor-vt--visible" "css_element" should exist
    And I click on ".c4lauthor-vt__btn--unstyle" "css_element"
    And I switch to the main frame
    And I click on "Apply" "button" in the ".tiny_c4lauthor__footer" "css_element"
    And I press "Save and display"
    Then "a[href='https://example.com']" "css_element" should exist in the "region-main" "region"
    And ".c4l-linkblock" "css_element" should not exist
    And I should see "Link name"
    # The address was shown as text beside the name, and belongs to the href alone.
    And I should not see "https://example.com"

  Scenario: A collapsible keeps the title the author wrote
    Given I am on the "Page 1" "page activity editing" page logged in as "teacher1"
    And I click on the "C4L Author" button for the "Page content" TinyMCE editor
    And I click on "Collapsible" "button" in the ".tiny_c4lauthor__sidebar" "css_element"
    When I switch to "tiny_c4lauthor__inner-iframe" class iframe
    And I hover ".c4lv-collapsible" "css_element"
    And ".c4lauthor-vt--visible" "css_element" should exist
    And I click on ".c4lauthor-vt__btn--unstyle" "css_element"
    And I switch to the main frame
    And I click on "Apply" "button" in the ".tiny_c4lauthor__footer" "css_element"
    And I press "Save and display"
    Then ".c4lv-collapsible" "css_element" should not exist
    # The title is the author's words, not a label the component wears.
    And I should see "Collapsible title"
    And I should see "Collapsible content here."
