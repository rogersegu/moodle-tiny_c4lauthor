@editor @editor_tiny @tiny @tiny_c4lauthor @javascript
Feature: Components an administrator writes themselves
  In order to offer my institution's own components
  As an administrator
  I need the plugin to insert the HTML I wrote, with my images, icon and placeholder

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | student1 | Student   | One      | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity | name    | course | idnumber | intro       | introformat | content            | contentformat |
      | page     | Page 1  | C1     | page1    | Description | 1           | <p>Hello world</p> | 1             |
      | forum    | Forum 1 | C1     | forum1   | Description | 1           |                    |               |
    And the following config values are set as admin:
      | customcompcount   | 1                                                         | tiny_c4lauthor |
      | customcompenable1 | 1                                                         | tiny_c4lauthor |
      | customcompname1   | Info box                                                  | tiny_c4lauthor |
      | customcompcode1   | <div class="{{CUSTOMCLASS}}"><p>{{PLACEHOLDER}}</p></div> | tiny_c4lauthor |

  Scenario: The component inserts the HTML the administrator wrote
    Given I am on the "Page 1" "page activity editing" page logged in as "teacher1"
    When I click on the "C4L Author" button for the "Page content" TinyMCE editor
    And I click on "Info box" "button" in the ".tiny_c4lauthor__sidebar" "css_element"
    And I click on "Apply" "button" in the ".tiny_c4lauthor__footer" "css_element"
    And I press "Save and display"
    Then I should see "Lorem ipsum dolor sit amet" in the ".c4lv-customcomp1.c4lv-custom-component" "css_element"

  Scenario: A component that is not enabled is not offered
    Given the following config values are set as admin:
      | customcompenable1 | 0 | tiny_c4lauthor |
    And I am on the "Page 1" "page activity editing" page logged in as "teacher1"
    When I click on the "C4L Author" button for the "Page content" TinyMCE editor
    Then "Info box" "button" should not exist in the ".tiny_c4lauthor__sidebar" "css_element"
    And "Key concept" "button" should exist in the ".tiny_c4lauthor__sidebar" "css_element"

  Scenario: The component carries the placeholder text the administrator wrote
    Given the following config values are set as admin:
      | customcomptext1 | Write the opening hours here | tiny_c4lauthor |
    And I am on the "Page 1" "page activity editing" page logged in as "teacher1"
    When I click on the "C4L Author" button for the "Page content" TinyMCE editor
    And I click on "Info box" "button" in the ".tiny_c4lauthor__sidebar" "css_element"
    And I click on "Apply" "button" in the ".tiny_c4lauthor__footer" "css_element"
    And I press "Save and display"
    Then I should see "Write the opening hours here" in the ".c4lv-customcomp1" "css_element"
    And I should not see "Lorem ipsum dolor sit amet"

  Scenario: An image from the bank is served, and a name that is not there leaves nothing behind
    Given the following config values are set as admin:
      | customcompcode1 | <div class="{{CUSTOMCLASS}}"><img src="{{c4l-checkmarks-icon.svg}}" alt=""><img src="{{nosuchimage.svg}}" alt=""><p>{{PLACEHOLDER}}</p></div> | tiny_c4lauthor |
    And the file "lib/editor/tiny/plugins/c4lauthor/pix/c4l-checkmarks-icon.svg" is stored in the "customimagesbank" setting of C4L Author
    And I am on the "Page 1" "page activity editing" page logged in as "teacher1"
    When I click on the "C4L Author" button for the "Page content" TinyMCE editor
    And I click on "Info box" "button" in the ".tiny_c4lauthor__sidebar" "css_element"
    And I click on "Apply" "button" in the ".tiny_c4lauthor__footer" "css_element"
    And I press "Save and display"
    Then "img[src*='c4l-checkmarks-icon.svg']" "css_element" should exist in the "region-main" "region"
    # An unknown name resolves to nothing at all, rather than to a broken link.
    And "img[src*='nosuchimage']" "css_element" should not exist in the "region-main" "region"

  Scenario: The button carries the icon the administrator uploaded
    Given the file "lib/editor/tiny/plugins/c4lauthor/pix/c4l-checkmarks-icon.svg" is stored in the "customcompicon1" setting of C4L Author
    And I am on the "Page 1" "page activity editing" page logged in as "teacher1"
    When I click on the "C4L Author" button for the "Page content" TinyMCE editor
    Then "img.c4l-custom-icon-img[src*='c4l-checkmarks-icon.svg']" "css_element" should exist in the ".tiny_c4lauthor__sidebar" "css_element"

  Scenario: A student is offered the administrator's own components
    Given I am on the "Forum 1" "forum activity" page logged in as "student1"
    And I click on "Add discussion topic" "link"
    When I click on the "C4L Author" button for the "Message" TinyMCE editor
    # The availability table has no row for the administrator's components, so their own
    # enable checkbox is what decides, for everyone.
    Then "Info box" "button" should exist in the ".tiny_c4lauthor__sidebar" "css_element"
