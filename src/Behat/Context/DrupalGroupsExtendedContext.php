<?php

/**
 * @file
 *
 * Context for Behat which contains utilities for Drupal Group module.
 */

namespace Metadrop\Behat\Context;

use Behat\Behat\Context\SnippetAcceptingContext;
use Webmozart\Assert\Assert;
use Drupal\group\Entity\Group;

/**
 * Utilities for testing Groups.
 */
class DrupalGroupsExtendedContext extends RawDrupalContext implements SnippetAcceptingContext {

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
    $properties = [
      'type' => $entity_type,
      'label' => $label
    ];

    $storage = \Drupal::entityTypeManager()->getStorage('group');
    $result = $storage->loadByProperties($properties);
    $entity = !empty($result) ? reset($result) : NULL;

    return !empty($entity) ? $entity->id() : NULL;
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
  public function subscribeUserToGroup($group_type, $gid, $user, $values = []) {
    /** @var \Drupal\group\Entity\Group $group */
    $group = Group::load($gid);

    if (!$group) {
      throw new \Exception("The Group does not exists.");
    }

    if (!empty($values)) {
      $group->addMember($user, $values);
    }
    else {
      $group->addMember($user);
    }
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
   * @Given user :user is subscribed to the group of type :group_type with name :name
   */
  public function userIsSubscribedToGroup($username, $group_type, $group_name) {
    $gid = $this->getEntityIdBylabel($group_type, $group_name);
    if (empty($gid)) {
      throw new \Exception($group_type . ' group with name ' . $group_name . ' doesn\'t exists!');
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
   * @Given user :user is subscribed to the group of type :group_type with name :name as a(n) :role role(s)
   */
  public function userIsSubscribedToGroupWithRoles($username, $group_type, $group_name, $roles) {
    $gid = $this->getEntityIdBylabel($group_type, $group_name);
    if (empty($gid)) {
      throw new \Exception($group_type . ' group with name ' . $group_name . ' doesn\'t exists!');
    }
    $user = user_load_by_name($username);
    if (empty($user)) {
      throw new \Exception($username . ' doesn\'t exists!');
    }
    $roles_array = explode(',', $roles);
    $this->subscribeUserToGroup($group_type, $gid, $user, ['group_roles' => $roles_array]);
  }
}
