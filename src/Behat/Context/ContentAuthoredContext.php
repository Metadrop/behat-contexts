<?php

namespace Metadrop\Behat\Context;

use Behat\Gherkin\Node\TableNode;

class ContentAuthoredContext extends RawDrupalContext {

  /**
   * Creates content of a given type authored by current user provided in the form:
   * | title    | status | created           |
   * | My title | 1      | 2014-10-17 8:00am |
   * | ...      | ...    | ...               |
   *
   * @Given :type content authored by current user:
   * @Given own :type content:
   */
  public function createNodeAuthoredCurrentUser($type, TableNode $nodesTable) {
    foreach ($nodesTable->getHash() as $nodeHash) {
      $node = (object) $nodeHash;
      $node->type = $type;
      $node->uid  = $this->getUserManager()->getCurrentUser()->uid;
      $this->nodeCreate($node);
    }
  }

}
