Feature: Account Management
  In order to manage my finances
  As a bank customer
  I need to be able to create and manage accounts

  Background:
    Given I am logged in as "john@example.com"

  Scenario: Creating a new account
    When I send a POST request to "/api/accounts"
    Then the response status code should be 201
    And the response should have a "uuid" field
    And the response should have a "balance" field
    And the response "balance" field should equal "0"

  # Note: Deposit and withdrawal balance verification scenarios removed
  # These operations use asynchronous workflows and are tested in PHPUnit feature tests
  # with proper workflow mocking (WorkflowStub::fake())

  Scenario: Preventing overdraft
    Given I have an account with balance 50.00 USD
    When I try to withdraw 100.00 USD from my account
    Then the withdrawal should fail with error "Insufficient balance"
    And my account balance should be 50.00 USD

  Scenario: Multi-currency account
    Given I have an account with balance 100.00 USD
    And I have an account with balance 50.00 EUR
    When I check my total balance
    Then I should see:
      | Currency | Balance |
      | USD      | 100.00  |
      | EUR      | 50.00   |