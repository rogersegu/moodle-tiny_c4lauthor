@editor @editor_tiny @tiny @tiny_c4lauthor @javascript
Feature: The administrator's component CSS reaches the content
  In order to style my own components without editing HTML
  As an administrator
  I need the plugin to send my CSS with every page

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "activities" exist:
      | activity | name   | course | idnumber | intro | introformat | content                                               | contentformat |
      | page     | Page 1 | C1     | page1    | Intro | 1           | <p>Hello world</p><p class="c4l-probe">Probe text</p> | 1             |

  Scenario: With nothing stored, the page is untouched
    When I am on the "Page 1" "page activity" page logged in as "admin"
    Then I should see "Hello world"
    And I should see "Probe text"

  Scenario: The stored CSS styles the content
    Given the following config values are set as admin:
      | custompreviewcss | .c4l-probe { display: none; } | tiny_c4lauthor |
    When I am on the "Page 1" "page activity" page logged in as "admin"
    Then I should see "Hello world"
    And I should not see "Probe text"

  Scenario: A closing tag in the setting does not swallow the page
    Given the following config values are set as admin:
      | custompreviewcss | .c4l-probe { color: red; }</style><p>swallowed</p> | tiny_c4lauthor |
    When I am on the "Page 1" "page activity" page logged in as "admin"
    Then I should see "Hello world"
    And I should see "Probe text"
    And I should not see "swallowed"
