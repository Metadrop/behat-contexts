<?php

namespace Metadrop\Behat\Hook\Scope;

/**
 * Available scope after creating entities.
 */
class AfterEntityCreateScope extends EntityScope {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return self::AFTER;
  }

}
