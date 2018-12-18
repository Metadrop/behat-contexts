<?php

namespace Metadrop\Behat\Context;

class NodeAccessContext extends RawDrupalContext {

  /**
   * Refresh node_access for the last node created.
   *
   * @param string $bundle
   *   Entity bundle.
   *
   * @Given the access of last node created is refreshed
   * @Given the access of last node created with :bundle bundle is refreshed
   */
  public function refreshLastNodeAccess($bundle = NULL) {
    $lastNodeId = $this->getCore()->getLastEntityId('node', $bundle);
    if (empty($lastNodeId)) {
      throw new \Exception("Can't get last node");
    }

    $node = $this->getCore()->entityLoadSingle('node', $lastNodeId);
    $this->getCore()->nodeAccessAcquireGrants($node);
  }

}