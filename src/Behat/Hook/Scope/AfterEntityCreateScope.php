<?php

namespace Metadrop\Behat\Hook\Scope;

use Drupal\DrupalExtension\Hook\Scope\BaseEntityScope;

class AfterEntityCreateScope extends BaseEntityScope
{
  
  public function getName()
  {
    return self::AFTER;
  }

}
