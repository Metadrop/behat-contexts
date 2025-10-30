<?php

namespace Metadrop\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Hook\BeforeScenario;
use Behat\Step\Given;
use Behat\Step\Then;


/**
 * Context used to generate random user data.
 *
 * This context doesn't create any user, it creates random user data that
 * can be used during tests.
 *
 * For example, if you need to fill a form with name, email and password you
 * can use this context. Typically, this is used to interact with remote APIs
 * through a form. You could use fixed values, but if the API requires
 * different values on each test run (because there's no way to clean previous
 * data created after submissions) this context can save your day.
 *
 * @package Metadrop\Behat\Context
 */
class UsersRandomContext extends RawDrupalContext {

  /**
   * Generated random user data.
   *
   * @var array
   */
  protected $randomUsers = [];

  /**
   * @var \Drupal\DrupalExtension\Context\MinkContext
   */
  protected $minkContext;

  /**
   * Get the necessary contexts.
   *
   *
   * @param BeforeScenarioScope $scope
   *   Scope del scenario.
   */
  #[BeforeScenario]
  public function gatherContexts(BeforeScenarioScope $scope) {
    $environment = $scope->getEnvironment();
    foreach ($environment->getContexts() as $context) {
      if ($context instanceof MinkContext) {
        $this->minkContext = $context;
      }
    }
  }

  /**
   * Generate random user data.
   *
   * This step creates random user data (name, email and password) so it can be
   * used on later steps.
   *
   *
   * Do not use spaces or special characters.
   * Example:
   * Given random users identified by:
   * | identifier  |
   * | debug       |
   * | email_test2 |
   *
   * @param \Behat\Gherkin\Node\TableNode $user_names_table
   *   Strings to identify generated random user data. Mainly used to identify
   *   the user data on other steps.
   */
  #[Given('random users identified by:')]
  public function generateRandomUsers(TableNode $user_names_table) {
    $hash = $user_names_table->getHash();
    $mail_names = array_map(function ($a) {
      return array_pop($a);
    }, $hash);
    foreach (array_values($mail_names) as $name) {
      $prefix = preg_replace("([^\w\_\d])", '', $name);
      $uuid = str_replace('-', '', \Drupal::service('uuid')->generate());
      $this->randomUsers[$prefix] = [
        'email' => $prefix . '+' . $uuid . '@metadrop.net',
        'username' => $prefix . '_' . $uuid,
        'password' => $prefix . '_' . $uuid,
      ];
    }
  }

  /**
   * Helper function to fill a form field with a given random user data.
   *
   * @param $field
   *   Field that would be populated with the requested random user data. The
   *   user  must have been generated previously.
   * @param $random_user_identifier
   *   Random generated user identifier.
   * @param $data_name
   *   User data to put in the form. It can be 'username', 'email' or 'password'.
   * @param $region
   *   The region where the form field is present. Optional.
   */
  protected function fillFormFieldWithRandomUserData($field, $random_user_identifier, $data_name, $region = NULL) {
    if (isset($this->randomUsers[$random_user_identifier])) {
      $random_user_data = $this->randomUsers[$random_user_identifier];
      if (!empty($region)) {
        $this->minkContext->regionFillField($field, $random_user_data[$data_name], $region);
      }
      else {
        $this->minkContext->fillField($field, $random_user_data[$data_name]);
      }
    }
    else {
      throw new \InvalidArgumentException("There does not exists a random user with the identifier '$random_user_identifier'");
    }
  }

  /**
   * Get data belonging to a random user.
   *
   * @param string $random_user_identifier
   *   User identifier.
   *
   * @return array
   *   User data.
   *
   * @throws \Exception
   *   When the user with a specific identifier.
   */
  public function getRandomUserData(string $random_user_identifier) {
    if (isset($this->randomUsers[$random_user_identifier])) {
      return $this->randomUsers[$random_user_identifier];
    }
    throw new \Exception(sprintf('There was no random data found for user with name "%s" ', $random_user_identifier));
  }

  /**
   * Step to fill a field with a previously generated random email.
   *
   * The random user data must have been generated previously.
   *
   * @param $field
   *   Field that would be populated with the random user data mail. The user
   *   must have been generated previously.
   * @param $random_user_identifier
   *   Random generated user identifier.
   * @param $region
   *   The region where the form field is present. Optional.
   */
  #[Then('I fill in :mail_field with random email from :random_user_identifier')]
  #[Then('I fill in :mail_field with random email from :random_user_identifier in the :region( region)')]
  public function iFillInWithRandomMail($field, $random_user_identifier, $region = NULL) {
    $this->fillFormFieldWithRandomUserData($field, $random_user_identifier, 'email', $region);
  }

  /**
   * Step to fill a field with a previously generated random username.
   *
   * The random user data must have been generated previously.
   *
   * @param $field
   *   Field that would be populated with the random user data username.
   * @param $random_user_identifier
   *   Random generated user identifier.
   * @param $region
   *   The region where the form field is present. Optional.
   */
  #[Then('I fill in :field with random username from :random_user_identifier')]
  #[Then('I fill in :field with random username from :random_user_identifier in the :region( region)')]
  public function iFillInWithRandomUserName($field, $random_user_identifier, $region = NULL) {
    $this->fillFormFieldWithRandomUserData($field, $random_user_identifier, 'username', $region);
  }

  /**
   * Step to fill a field with a previously generated random password.
   *
   * The random user data must have been generated previously.
   *
   * @param $field
   *   Field that would be populated with the random user data password.
   * @param $random_user_identifier
   *   Random generated user identifier.
   * @param $region
   *   The region where the form field is present. Optional.
   */
  #[Then('I fill in :field with random password from :random_user_identifier')]
  #[Then('I fill in :field with random password from :random_user_identifier in the :region( region)')]
  public function iFillInWithRandomUserPassword($field, $random_user_identifier, $region = NULL) {
    $this->fillFormFieldWithRandomUserData($field, $random_user_identifier, 'password', $region);
  }
}
