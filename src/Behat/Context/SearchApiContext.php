<?php


namespace Metadrop\Behat\Context;

use Metadrop\Behat\Hook\Scope\EntityScope;
use Metadrop\Behat\Hook\Scope\AfterEntityCreateScope;
use Drupal\Core\Entity\EntityInterface;
use Drupal\DrupalExtension\Hook\Scope\AfterNodeCreateScope;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Integrate with search api.
 *
 * Forces the content to be automatically
 * indexed after node / entity creation.
 *
 * @package Metadrop\Behat\Context
 */
class SearchApiContext extends RawDrupalContext
{

  /**
   * Index nodes after they have been created.
   *
   * @afterNodeCreate
   *
   * @deprecated use tag @search_api instead
   * @see https://www.drupal.org/project/search_api/issues/3263875
   */
  public function indexNodesAfterCreate(AfterNodeCreateScope $scope) {
    $node = $scope->getEntity();
    if (is_object($node) && isset($node->nid)) {

      $node = \Drupal::entityTypeManager()->getStorage('node')->load($node->nid);
      if ($node instanceof NodeInterface) {
        $this->forceEntityIndex($node);
      }
    }
  }

  /**
   * In case there are referenced entities, try to index the parent.
   *
   * @afterEntityCreate
   *
   * @deprecated use tag @search_api instead
   * @see https://www.drupal.org/project/search_api/issues/3263875
   */
  public function afterEntityCreate(AfterEntityCreateScope $scope) {
    $entity = $scope->getEntity();
    $entity_type = $scope->getEntityType();
    switch ($entity_type) {
      case 'paragraph':
        $paragraph = \Drupal::entityTypeManager()->getStorage('paragraph')->loadUnchanged($entity->id);
        if ($paragraph instanceof ParagraphInterface) {
          do {
            $parent = $paragraph->getParentEntity();
          } while ($parent instanceof ParagraphInterface);
          if ($parent instanceof NodeInterface) {
            $this->forceEntityIndex($parent);
          }
        }
        break;
    }
  }

  /**
   * Force an entity to be tracked.
   *
   * @param EntityInterface $entity
   *   Entity.
   */
  public function forceEntityIndex(EntityInterface $entity) {
    $tracking_manager = \Drupal::service('search_api.entity_datasource.tracking_manager');
    $search_api_post_request_indexing = \Drupal::service('search_api.post_request_indexing');

    $indexes = $tracking_manager->getIndexesForEntity($entity);
    // Compute the item IDs for all languages of the entity.
    $item_ids = [];
    $entity_id = $entity->id();
    foreach (array_keys($entity->getTranslationLanguages()) as $langcode) {
      $item_ids[] = 'entity:' . $entity->getEntityTypeId() . '/' . $entity_id . ':' . $langcode;
    }
    foreach ($indexes as $index) {
      $search_api_post_request_indexing->registerIndexingOperation($index->id(), $item_ids);
    }
    $this->indexPendingSearchApiItems();

    // Sleep needed to prevent the test fails as it didn't happen too much
    // time to invalidate search api cache tags.
    // In the case this is not solid enough, the definitive solution will be checking
    // the search api cache tags did expire each 1 millisecond.
    usleep(pow(10, 5) * 3);
  }

  /**
   * Index the search api items requested to index.
   */
  protected function indexPendingSearchApiItems() {
    /** @var \Drupal\search_api\Utility\PostRequestIndexingInterface $search_api_post_request_indexing */
    $search_api_post_request_indexing = \Drupal::service('search_api.post_request_indexing');
    $search_api_post_request_indexing->destruct();
  }

}
