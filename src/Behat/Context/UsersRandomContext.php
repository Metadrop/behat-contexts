<?php

namespace Metadrop\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\DrupalContext;

class UsersRandomContext extends DrupalContext {

  /**
   * Random users generated.
   *
   * @var array
   */
  protected $randomUsers = [];

  /**
   * Generate random users.
   *
   * Do not use spaces or special characters.
   * Example:
   * Given random users:
   * | name        |
   * | debug       |
   * | email_test2 |
   *
   * @param \Behat\Gherkin\Node\TableNode $user_names_table
   *   Names to identify random users.
   *
   * @Given random users:
   */
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
   * Step for random user emails.
   *
   * @Then I fill in :arg1 with random email :arg2
   */
  public function iFillInWithRandomMail($field, $random_mail_name) {
    if (isset($this->randomUsers[$random_mail_name])) {
      $random_mail = $this->randomUsers[$random_mail_name];
      $this->minkContext->fillField($field, $random_mail['email']);
    }
  }

  /**
   * Step for random user usernames.
   *
   * @Then I fill in :arg1 with random username :arg2
   */
  public function iFillInWithRandomUserName($field, $random_mail_name) {
    if (isset($this->randomUsers[$random_mail_name])) {
      $random_mail = $this->randomUsers[$random_mail_name];
      $this->minkContext->fillField($field, $random_mail['username']);
    }
  }

  /**
   * Step for random user password.
   *
   * @Then I fill in :arg1 with random password :arg2
   */
  public function iFillInWithRandomUserPassword($field, $random_mail_name) {
    if (isset($this->randomUsers[$random_mail_name])) {
      $random_mail = $this->randomUsers[$random_mail_name];
      $this->minkContext->fillField($field, $random_mail['username']);
    }
  }

}
