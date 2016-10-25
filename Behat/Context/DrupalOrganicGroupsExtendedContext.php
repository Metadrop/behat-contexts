<?php

/**
 * @file
 *
 * DrupalUtilsContext Context for Behat.
 *
 */

namespace Metadrop\Behat\Context;

use Behat\Behat\Context\SnippetAcceptingContext;
use Drupal\DrupalExtension\Context\RawDrupalContext;

class DrupalOrganicGroupsExtendedContext extends RawDrupalContext implements SnippetAcceptingContext {

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
