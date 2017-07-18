<?php
/**
 * @file
 *
 * Context for Behat which contains utilities for Organic groups.
 */

namespace Metadrop\Behat\Context;

use Behat\Behat\Context\SnippetAcceptingContext;
use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * Utilities for testing organic groups.
 */
class DrupalOrganicGroupsExtendedContext extends RawDrupalContext implements SnippetAcceptingContext {

  /**
   * Get entity id by entity label.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $label
   *   Label.
   *
   * @return int
   *   Entity id.
   */
  public function getEntityIdBylabel($entity_type, $label) {
    $entity_info = entity_get_info($entity_type);

    // Add label key to user entity.
    // this is done by drupaldriver for some similar purpose.
    // @see Drupal\Driver\Fields\Drupal7\EntityReferenceHandler::expand()
    if ($entity_type == 'user') {
      $entity_info['entity keys']['label'] = 'name';
    }
    if (!empty($entity_info['entity keys']['label'])) {
      $entity_id = db_select($entity_info['base table'], 'entity')
        ->fields('entity', [$entity_info['entity keys']['id']])
        ->condition($entity_info['entity keys']['label'], $label)
        ->execute()
        ->fetchField();
    }
    else {
      $entity_id = NULL;
    }

    return $entity_id;
  }

  /**
   * Subscribes user to group
   *
   * @param string $username
   *   User name.
   * @param string $group_type
   *   Group type.
   * @param string $group_name
   *   Group name.
   *
   * @Given user :user is subscribed to the group of type :group_type group with name :name
   */
  public function userIsSubscribedToGroup($username, $group_type, $group_name) {
    $gid = $this->getEntityIdBylabel($group_type, $group_name);
    if (empty($gid)) {
      throw new Exception($group_type . ' group with name ' . $group_name . ' doesn\'t exists!');
    }
    $user = user_load_by_name($username);
    $this->subscribeUserToGroup($group_type, $gid, $user);
  }

  /**
   * Subscribes user to group
   *
   * @param string $username
   *   User name.
   * @param string $group_type
   *   Group type.
   * @param string $group_name
   *   Group name.
   *
   * @Given user :user is subscribed to the group of type :group_type group with name :name as a(n) :role role(s)
   */
  public function userIsSubscribedToGroupWithRoles($username, $group_type, $group_name, $roles) {
    $gid = $this->getEntityIdBylabel($group_type, $group_name);
    if (empty($gid)) {
      throw new Exception($group_type . ' group with name ' . $group_name . ' doesn\'t exists!');
    }

    $user = user_load_by_name($username);
    $this->subscribeUserToGroup($group_type, $gid, $user);

    $roles_array = explode(',', $roles);
    foreach ($roles_array as $role) {
      $this->addUserGroupRole($group_type, $gid, $role, $user);
    }
  }

  /**
   * Subscribe user to group.
   *
   * @param string $group_type
   *   Entity type which user is subcript.
   * @param int $gid
   *   Group id.
   * @param object $user
   *   User being subscript.
   */
  public function subscribeUserToGroup($group_type, $gid, $user) {
    $membership = og_group($group_type, $gid, array(
      "entity type" => 'user',
      "entity" => $user,
    ));

    if (!$membership) {
      throw new \Exception("The Organic Group membership could not be created.");
    }
  }

  /**
   * Subscribe user to group with specific role.
   *
   * @param string $group_type
   *   Entity type which user is subcript.
   * @param int $gid
   *   Group id.
   * @param string $role
   *   Role.
   * @param object $user
   *   User being subscript.
   */
  public function subscribeUserToGroupWithRole($group_type, $gid, $role, $user)
  {
    // Subscript user to group.
    $this->subscribeUserToGroup($group_type, $gid, $user);

    // Load og entity.
    $this->subscribeUserToGroupWithRole($group_type, $gid, $role, $user);
  }

  /**
   * Add user role on specific group.
   *
   * @param string $group_type
   *   Group entity type. For example, "node".
   * @param int $gid
   *   Group entity id.
   * @param string $role
   *   Group role.
   * @param object $user
   *   user.
   *
   * @throws \Exception
   *   When role doesn't exists.
   */
  public function addUserGroupRole($group_type, $gid, $role, $user) {
    $entities = entity_load($group_type, array($gid));
    $entity = reset($entities);

    $og_roles = og_roles($group_type, $entity->type, $gid, FALSE, FALSE);
    $rid = array_search($role, $og_roles);

    if (!$rid) {
      throw new \Exception("'$role' is not a valid group role.");
    }

    og_role_grant($group_type, $gid, $user->uid, $rid);
  }

}
