<?php

namespace Metadrop\Behat\Hook\Scope;

/**
 * Available scope before creating entities.
 */
class BeforeEntityCreateScope extends EntityScope {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return self::BEFORE;
  }

}
