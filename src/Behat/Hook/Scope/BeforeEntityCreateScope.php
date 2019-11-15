<?php

namespace Metadrop\Behat\Hook\Scope;

use Drupal\DrupalExtension\Hook\Scope\BaseEntityScope;

class BeforeEntityCreateScope extends EntityScope
{
  public function getName()
  {
    return self::BEFORE;
  }

}
