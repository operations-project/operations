Feature:
  @api
  Scenario: Site Report works
    Given I am logged in as a user with the "access about site page,access site history page" permission
    And I am at "admin/about/site"
    Then I should see "Site State"
    When I click "History"
    Then I should see "Initial site entity created on installation of site.module."

  @api
  Scenario: Save report on Config works.
    Given I am logged in as a user with the "access about site page,access site history page,administer site entity settings,administer site configuration" permission
    And I am at "admin/about/site"
    When I click "Settings"
    And I check the box "Save on config changes"
    And I press "Save"
    Then I should see "Site report saved:"
    And I should see "Configs site.site_definition.self updated at"

    Then I am at "admin/config/system/site-information"
    When I fill in "Site name" with "Behat Tested Site"
    And I press "Save configuration"
    # Then I should see "Site report saved:"

    Then I am at "admin/about/site/history"
    Then I should see "Configs system.site updated at"

    # @TODO: Add test for site entity title changes here.
