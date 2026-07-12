@aiprovider_anthropic
Feature: Configure Anthropic Claude API provider
  In order to use the Anthropic Claude API for AI features
  As an admin
  I can create and configure an Anthropic Claude provider instance

  @javascript
  Scenario: An administrator can create an Anthropic Claude provider instance
    Given I am logged in as "admin"
    And I navigate to "AI > AI providers" in site administration
    When I click on "Create a new provider instance" "link"
    And I select "Anthropic Claude API provider" from the "Choose AI provider plugin" singleselect
    And I set the following fields to these values:
      | Name for instance | Anthropic test instance |
      | Anthropic API key | test_api_key_123        |
    And I click on "Create instance" "button"
    Then I should see "Anthropic test instance AI provider instance created"
    And I should see "Anthropic test instance"

  @javascript
  Scenario: An administrator can enable and disable an Anthropic Claude provider instance
    Given the following "core_ai > ai providers" exist:
      | provider             | name             | enabled | apikey           |
      | aiprovider_anthropic | Anthropic test   | 0       | test_api_key_123 |
    And I am logged in as "admin"
    And I navigate to "AI > AI providers" in site administration
    When I toggle the "Enable Anthropic test" admin switch "on"
    Then I should see "Anthropic test enabled."
    And I reload the page
    And I should see "Disable Anthropic test"
    When I toggle the "Disable Anthropic test" admin switch "off"
    Then I should see "Anthropic test disabled."

  @javascript
  Scenario: Anthropic Claude provider only shows text actions (no image generation)
    Given the following "core_ai > ai providers" exist:
      | provider             | name           | enabled | apikey           |
      | aiprovider_anthropic | Anthropic test | 1       | test_api_key_123 |
    And I am logged in as "admin"
    And I navigate to "AI > AI providers" in site administration
    And I click on the "Settings" link in the table row containing "Anthropic test"
    Then I should see "Configure provider instance"
    And I should see "Generate text"
    And I should see "Summarise text"
    And I should see "Explain text"
    And I should not see "Generate image"

  @javascript
  Scenario: An administrator can configure Anthropic Claude action settings
    Given the following "core_ai > ai providers" exist:
      | provider             | name           | enabled | apikey           |
      | aiprovider_anthropic | Anthropic test | 1       | test_api_key_123 |
    And I am logged in as "admin"
    And I navigate to "AI > AI providers" in site administration
    And I click on the "Settings" link in the table row containing "Anthropic test"
    And I click on the "Settings" link in the table row containing "Generate text"
    Then I should see "Generate text action settings"
    And the "AI model" select box should contain "Claude Haiku 4.5"
    And the "AI model" select box should contain "Claude Sonnet 4.5"
    And the "AI model" select box should contain "Claude Sonnet 5"
    And the "AI model" select box should contain "Claude Opus 4.1"
    And the "AI model" select box should contain "Claude Opus 4.5"
    And the "AI model" select box should contain "Claude Opus 4.8"
    When I set the following fields to these values:
      | AI model   | Claude Opus 4.8 |
      | Max tokens | 4096            |
    And I click on "Save changes" "button"
    Then I should see "Generate text action settings updated"
    When I click on the "Settings" link in the table row containing "Generate text"
    Then the field "AI model" matches value "Claude Opus 4.8"
    And the field "Max tokens" matches value "4096"

  @javascript
  Scenario: An administrator can delete an Anthropic Claude provider instance
    Given the following "core_ai > ai providers" exist:
      | provider             | name           | enabled | apikey           |
      | aiprovider_anthropic | Anthropic test | 0       | test_api_key_123 |
    And I am logged in as "admin"
    And I navigate to "AI > AI providers" in site administration
    And I click on the "Delete" link in the table row containing "Anthropic test"
    And "Delete AI provider instance" "dialogue" should be visible
    And I click on "Delete" "button" in the "Delete AI provider instance" "dialogue"
    Then I should see "Anthropic test AI provider instance deleted"
