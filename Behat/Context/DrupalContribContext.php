<?php

/**
 * @file
 *
 * DrupalExtendedContext Context for Behat.
 *
 */

namespace Metadrop\Behat\Context;

use Behat\Behat\Context\SnippetAcceptingContext;
use Drupal\DrupalExtension\Context\RawDrupalContext;

class DrupalContribContext extends RawDrupalContext implements SnippetAcceptingContext {

  /**
   * Creates profile2
   * | type        | label  | uid |
   * | profileType | Label  | 48  |
   * | ...         | ...    | ... |
   *
   * @NOTE: The uid could be the usermail or the username
   * @see profile2Create()
   *
   * @Given profile2:
   */
  public function createProfile2(TableNode $nodesTable) {
    foreach ($nodesTable->getHash() as $nodeHash) {
      $profile = (object) $nodeHash;

      $this->profile2Create($profile);
    }
  }

  /**
   * Creates a profile2 account.
   *
   * @param object $entity
   * @return type
   */
  public function profile2Create($entity) {

    // @TODO: remove created profile2 after test execution.

    if (!is_numeric($entity->uid)) {
      if (valid_email_address($entity->uid)) {
        $entity->uid = db_query('SELECT uid FROM users WHERE mail = :mail', array(
          ':mail' => $entity->uid
        ))->fetchField();
      }
      else {
        $entity->uid = db_query('SELECT uid FROM users WHERE name = :name', array(
          ':name' => $entity->uid
        ))->fetchField();
      }
    }

    // Save default values:
    $profile = profile2_create((array) $entity);
    profile2_save($profile);

    // Use EMW to save field values:
    // Force reload profile to work with EMW
    $wrapper = entity_metadata_wrapper('profile2', $profile->pid);

    // @TODO: extract properties from entity.
    $keys = array(
      'type',
      'label',
      'uid',
      'created',
      'changed',
    );
    foreach ($entity as $key => $value) {
      if (!in_array($key, $keys)) {
       $wrapper->{$key} = $value;
      }
    }

    $wrapper->save();

    return $wrapper->value();
  }

}
