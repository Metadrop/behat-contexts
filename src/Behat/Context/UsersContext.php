<?php

namespace Metadrop\Behat\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Drupal\DrupalExtension\Context\DrupalContext;
use Behat\Hook\BeforeScenario;
use Behat\Step\Given;
use Behat\Step\Then;


class UsersContext extends RawDrupalContext {

  /**
   * @var \Drupal\DrupalExtension\Context\DrupalContext
   */
  protected $drupalContext;

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
    $classesArray = $environment->getContextClasses();
    foreach ($classesArray as $class_name) {
      if (is_subclass_of($class_name, DrupalContext::class) || $class_name == DrupalContext::class) {
        $this->drupalContext = $environment->getContext($class_name);
        break;
      }
    }
  }

  /**
   * Check that user with mail exists.
   */
  #[Then('user with mail :mail exists')]
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
   */
  #[Then('user with mail :mail not exists')]
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
    // Because Behat process is alive during the whole test suite, we should
    // clear user cache.
    // @TODO: We may clear only the user being checked. However, the process
    // to get the uid is not direct. Let's just clear the complete cache for
    // now.
    $this->getCore()->staticEntityCacheClear('user');

    $account = $this->locateAccount($user);

    if (!$account) {
      throw new \Exception("Given user does not exist!");
    }

    $roles_to_check = $this->roleString2Array($role);

    // Get current roles for the user.
    $account_roles = array_map('strtolower', $this->getCore()->getUserRoles($account));

    // Calculate...
    $common_roles = array_intersect($roles_to_check, $account_roles);
    $roles_not_in_account = array_diff($roles_to_check, $account_roles);

    // ...and check!
    if (!$not && count($roles_not_in_account)) {
      throw new \Exception("Given user does not have the role(s) " . implode(', ', $roles_not_in_account));
    }
    elseif ($not && count($common_roles)) {
      throw new \Exception("Given user has the role(s) " . implode(', ', $common_roles));
    }
  }

  /**
   * Locates and load an account using a user locator.
   *
   * A user locator can be:
   *   - NULL: This means the current user.
   *   - A username.
   *   - A user's email .
   *   - A uid.
   *
   * @param $string $locator
   * @return \Drupal\Core\Entity\EntityInterface|mixed
   *   the user located by the given locator.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function locateAccount($locator) {
    if (empty($locator)) {
      $current_user = $this->getUserManager()->getCurrentUser();
      $account = $this->getCore()->loadUserByProperty('uid', $current_user->uid);
    }
    else {
      $property = $this->getCore()->getUserPropertyByName($locator);
      $account = $this->getCore()->loadUserByProperty($property, $locator);
    }

    return $account;
  }

  /**
   * Converts a comma-separated list of role names into an array of role names.
   *
   * Role names are converted into lower case.
   *
   * So, from a string like "MyRole, MySecondRole" this function returns:
   *   array("myrole", "mysecondrole");
   *
   * @param string $roles_string
   *   A comma-separated list of role names.
   * @return array
   *   An array of role names
   */
  protected function roleString2Array($roles_string) {
    $roles_raw_array = explode(',', $roles_string);
    return array_map(function ($item) {
      return strtolower(trim($item));
    }, $roles_raw_array);
  }

  /**
   * Check the user has a specific role.
   *
   * @see userRoleCheck()
   */
  #[Then('I should have the :role role(s)')]
  #[Then('the user :user should have the :role role(s)')]
  public function userShouldHaveTheRole($role, $user = NULL) {
    return $this->userRoleCheck($role, $user);
  }

  /**
   * Check the user does not have a specific role.
   *
   * @see userRoleCheck()
   */
  #[Then('I should not have the :role role(s)')]
  #[Then('the user :user should not have the :role role(s)')]
  public function userShouldNotHaveTheRole($role, $user = NULL) {
    return $this->userRoleCheck($role, $user, TRUE);
  }

  /**
   * Users with any type of role.
   */
  #[Given('I am a(n) user with :role role')]
  public function assertUserByRole($role) {
    if (!$this->drupalContext instanceof DrupalContext) {
      throw new \Exception("The context 'Drupal\DrupalExtension\Context\DrupalContext' is not found in the suite environment. Please check behat.yml file");
    }

    if ($role == 'anonymous') {
      $this->drupalContext->assertAnonymousUser();
    }
    else {
      $this->drupalContext->assertAuthenticatedByRole($role);
    }
  }

}
