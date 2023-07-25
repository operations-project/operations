Feature:
  @api
  Scenario:
    Given I am logged in as a user with the "access about site page,access site history page" permission
    And I am at "admin/about/site"
    Then I should see "Site State"
    When I click "History"
    Then I should see "Initial site entity created on installation of site.module."
