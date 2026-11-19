@editor @editor_tiny @tiny @tiny_c4lauthor @javascript
Feature: Precision mode edits a text of several paragraphs
  In order to correct a long text without hunting through it
  As a teacher
  I need precision mode to show a component's text whole and to leave its formatting alone

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
      | activity | name   | course | idnumber | intro       | introformat | content                                                                                                                                                                                                | contentformat |
      | page     | Page 1 | C1     | page1    | Description | 1           | <p class="c4l-spacer">&nbsp;</p><div class="c4lv-allpurposecard"><p><span data-id="T1-1">Alpha with <strong>bold</strong> inside</span></p><p>Beta the second</p></div><p class="c4l-spacer">&nbsp;</p> | 1             |

  Scenario: The paragraphs of a text are one box, a blank line between them
    Given I am on the "Page 1" "page activity editing" page logged in as "teacher1"
    And I click on the "C4L Author" button for the "Page content" TinyMCE editor
    When I click on "Precision" "button" in the ".tiny_c4lauthor__view-switcher" "css_element"
    And I switch to "tiny_c4lauthor__precision-iframe" class iframe
    And I click on "Alpha" "text"
    And I switch to the main frame
    # By id, not by its "Text" label: the page's own form is open behind the modal.
    Then the field "tiny_c4lauthor__precision-field-0" matches multiline:
      """
      Alpha with bold inside

      Beta the second
      """

  Scenario: Editing a text leaves the formatting of the rest of it alone
    Given I am on the "Page 1" "page activity editing" page logged in as "teacher1"
    And I click on the "C4L Author" button for the "Page content" TinyMCE editor
    And I click on "Precision" "button" in the ".tiny_c4lauthor__view-switcher" "css_element"
    And I switch to "tiny_c4lauthor__precision-iframe" class iframe
    And I click on "Alpha" "text"
    And I switch to the main frame
    # At the end of the box, after the last paragraph, so that nothing else is touched.
    When I click on "//textarea[@data-field='0']" "xpath_element"
    And I press the ctrl end key
    And I type " extra"
    And I click on "Content" "button" in the ".tiny_c4lauthor__view-switcher" "css_element"
    And I click on "Apply" "button" in the ".tiny_c4lauthor__footer" "css_element"
    And I press "Save and display"
    Then I should see "Beta the second extra" in the ".c4lv-allpurposecard" "css_element"
    # The first paragraph is untouched, and still a paragraph of its own, with its bold.
    And I should see "Alpha with bold inside" in the ".c4lv-allpurposecard" "css_element"
    And "//div[contains(@class,'c4lv-allpurposecard')]/p[2]" "xpath_element" should exist
    And "//div[contains(@class,'c4lv-allpurposecard')]//strong" "xpath_element" should exist
