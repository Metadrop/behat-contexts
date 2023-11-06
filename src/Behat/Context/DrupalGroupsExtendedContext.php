<?php

/**
 * @file
 *
 * Context for Behat which contains utilities for Drupal Group module.
 */

namespace Metadrop\Behat\Context;

use Webmozart\Assert\Assert;
use Drupal\group\Entity\Group;

/**
 * Utilities for testing Groups.
 */
class DrupalGroupsExtendedContext extends RawDrupalContext {

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
   * Get entity by entity label.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $label
   *   Label.
   *
   * @return \Drupal\group\Entity\Group
   *   Entity.
   */
  public function getEntityBylabel($entity_type, $label) {
    $properties = [
      'type' => $entity_type,
      'label' => $label
    ];

    $storage = \Drupal::entityTypeManager()->getStorage('group');
    $result = $storage->loadByProperties($properties);
    $entity = !empty($result) ? reset($result) : NULL;

    return $entity ?: NULL;
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
   * Sets the owner of a group.
   *
   * @param $username string
   * @param $group_type string
   * @param $group_name string
   *
   * @Given the user :user_name is the owner of the group type :group_type with name :group_name
   */
  public function groupSetOwnerByUserName($user_name, $group_type, $group_name) {
    $user = user_load_by_name($user_name);
    /** @var \Drupal\group\Entity\Group $group */
    $group = $this->getEntityBylabel($group_type, $group_name);
    $group->setOwnerId($user->id());
    $group->save();
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
   * Adds content to Group
   *
   * @param string $title
   *   Content title.
   * @param string $bundle
   *   Content title.
   * @param string $group_type
   *   Group type.
   * @param string $group_name
   *   Group name.
   *
   * @Given content :title with bundle :bundle is subscribed to the group of type :group_type with name :name
   */
  public function contentIsSubscribedToGroup($title, $bundle, $group_type, $group_name) {
    $gid = $this->getEntityIdBylabel($group_type, $group_name);
    if (empty($gid)) {
      throw new \Exception($group_type . ' group with name ' . $group_name . ' doesn\'t exists!');
    }
    $properties = [
      'type' => $bundle,
      'title' => $title,
    ];
    $entities = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties($properties);
    if (empty($entities)) {
      throw new \Exception('Content ' . $title . ' doesn\'t exists.');
    }
    $entity = reset($entities);

    /** @var \Drupal\group\Entity\Group $group */
    $group = Group::load($gid);

    if (!$group) {
      throw new \Exception("The Group does not exists.");
    }
      $group->addContent($entity, 'group_node:' . $entity->bundle());
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
