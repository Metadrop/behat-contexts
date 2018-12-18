<?php

namespace Metadrop\Behat\Context;

class EntityContext extends RawDrupalContext {

  /**
   * Go to last entity created.
   *
   * @Given I go to the last entity :entity created
   * @Given I go to the last entity :entity with :bundle bundle created
   * @Given I go to :subpath of the last entity :entity created
   * @Given I go to :subpath of the last entity :entity with :bundle bundle created
   *
   * @USECORE
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $bundle
   *   Entity bundle.
   * @param string $subpath
   *   Entity bundle.
   */
  public function goToTheLastEntityCreated($entity_type, $bundle = NULL, $subpath = NULL) {
    $last_entity = $this->getCore()->getLastEntityId($entity_type, $bundle);
    if (empty($last_entity)) {
      throw new \Exception("Imposible to go to path: the entity does not exists");
    }

    $entity = $this->getCore()->entityLoadSingle($entity_type, $last_entity);
    if (!empty($entity)) {
      $uri = $this->getCore()->entityUri($entity_type, $entity);
      $path = empty($subpath) ? $uri : $uri . '/' . $subpath;
      $this->getSession()->visit($this->locatePath($path));
    }
  }

}
