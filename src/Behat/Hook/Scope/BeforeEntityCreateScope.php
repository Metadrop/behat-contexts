<?php

namespace Metadrop\Behat\Hook\Scope;

/**
 * Scope before creating entities.
 */
class BeforeEntityCreateScope extends EntityScope {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return self::BEFORE;
  }

}
