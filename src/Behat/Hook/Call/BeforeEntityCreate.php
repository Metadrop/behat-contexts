<?php

namespace Metadrop\Behat\Hook\Call;

use Drupal\DrupalExtension\Hook\Call\EntityHook;
use Drupal\DrupalExtension\Hook\Scope\EntityScope;

/**
 * Hook before entity create.
 */
class BeforeEntityCreate extends EntityHook {

  /**
   * Initializes hook.
   */
  public function __construct($filterString, $callable, $description = NULL) {
    parent::__construct(EntityScope::BEFORE, $filterString, $callable, $description);
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'BeforeEntityCreate';
  }

}
