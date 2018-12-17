<?php

namespace Metadrop\Behat\Cores\Traits;

trait UsersTrait {

  /**
   * Gets user property by name.
   *
   * This function tries to figure out which kind to identificator is refering to
   * in an "smart" way.
   *
   * @param string $name
   *   The identifier
   *   Examples: "admin", "12", "example@example.com"
   *
   * @return string
   *   The property
   */
  public function getUserPropertyByName($name) {
    if (valid_email_address($name)) {
      $property = 'mail';
    }
    elseif (is_numeric($name)) {
      $property = 'uid';
    }
    else {
      $property = 'name';
    }
    return $property;
  }

  /**
   * {@inheritdoc}
   */
  public function loadUserByMail($mail) {
    $user = user_load_by_mail($mail);
    Assert::notEq($user, FALSE, 'User with mail "' . $mail . '" exists.');
    return $user;
  }

}