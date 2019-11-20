<?php

namespace Metadrop\Behat\Hook\Scope;

use Drupal\DrupalExtension\Hook\Scope\BaseEntityScope;

/**
 * Scope after creating entities.
 */
class AfterEntityCreateScope extends BaseEntityScope {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return self::AFTER;
  }

}
