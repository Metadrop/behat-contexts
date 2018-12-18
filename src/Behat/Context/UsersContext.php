<?php

namespace Metadrop\Behat\Context;

class UsersContext extends RawDrupalContext {

  /**
   * Check that user with mail exists.
   *
   * @Then user with mail :mail exists
   */
  public function userWithMailExists($mail, $exists = TRUE) {
    $user = $this->getCore()->loadUserByProperty('mail', $mail);
    if (!$user && $exists) {
      throw new \Exception("The user with mail '" . $mail . "' was not found.");
    }
    elseif (!empty($user) && !$exists) {
      throw new \Exception("The user with mail '" . $mail . "' exists.");
    }
  }

  /**
   * Check that user with mail not exists.
   *
   * @Then user with mail :mail not exists
   */
  public function userWithMailNotExists($mail) {
    $this->userWithMailExists($mail, FALSE);
  }

  /**
   * Check the user has or not a specific role.
   *
   * @param string $role
   *   Role name(s) separated by comma.
   * @param string $user
   *   User identifier: username | mail | uid or NULL to current user.
   * @param bool $not
   *   True if the user should NOT have the specific roles.
   */
  public function userRoleCheck($role, $user = NULL, $not = FALSE) {
    if (empty($user)) {
      $current_user = $this->getUserManager()->getCurrentUser();
      $account = $this->getCore()->loadUserByProperty('uid', $current_user->uid);
    }
    else {
      $property = $this->getCore()->getUserPropertyByName($user);
      $account = $this->getCore()->loadUserByProperty($property, $user);
    }

    if ($account) {
      $roles = explode(',', $role);
      $roles = array_map('trim', $roles);
      // Case insensitive:
      $roles = array_map('strtolower', $roles);
      $aroles = array_map('strtolower', $this->getCore()->getUserRoles($account));
      foreach ($roles as $role) {
        if (!$not && !in_array($role, $aroles)) {
          throw new \Exception("Given user does not have the role $role");
        }
        else if ($not && in_array($role, $aroles)) {
          throw new \Exception("Given user have the role $role");
        }
      }
    }
    else {
      throw new \Exception("Given user does not exists!");
    }
  }

  /**
   * Check the user has a specific role.
   *
   * @see userRoleCheck()
   *
   * @Then I should have the :role role(s)
   * @Then the user :user should have the :role role(s)
   */
  public function userShouldHaveTheRole($role, $user = NULL) {
    return $this->userRoleCheck($role, $user);
  }

  /**
   * Check the user does not have a specific role.
   *
   * @see userRoleCheck()
   *
   * @Then I should not have the :role role(s)
   * @Then the user :user should not have the :role role(s)
   */
  public function userShouldNotHaveTheRole($role, $user = NULL) {
    return $this->userRoleCheck($role, $user, TRUE);
  }

}