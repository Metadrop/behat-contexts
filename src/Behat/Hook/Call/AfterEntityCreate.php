<?php

namespace Metadrop\Behat\Hook\Call;

use Drupal\DrupalExtension\Hook\Call\EntityHook;
use Drupal\DrupalExtension\Hook\Scope\EntityScope;

/**
 * Hook after entity create.
 */
class AfterEntityCreate extends EntityHook {

  /**
   * Initializes hook.
   */
  public function __construct($filterString, $callable, $description = NULL) {
    parent::__construct(EntityScope::AFTER, $filterString, $callable, $description);
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'AfterEntityCreate';
  }

}
