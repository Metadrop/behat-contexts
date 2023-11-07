<?php

namespace Metadrop\Behat\Cores;

use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\ultimate_cron\Entity\CronJob;
use http\Exception\InvalidArgumentException;
use NuvoleWeb\Drupal\Driver\Cores\Drupal8 as OriginalDrupal8;
use Metadrop\Behat\Cores\Traits\UsersTrait;
use Metadrop\Behat\Cores\Traits\CronTrait;
use Metadrop\Behat\Cores\Traits\FileTrait;
use Metadrop\Behat\Cores\Traits\EntityTrait;
use Webmozart\Assert\Assert;
use Behat\Behat\Tester\Exception\PendingException;
use Drupal\user\Entity\User;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Component\Render\FormattableMarkup;
use Metadrop\Exception\EntityNotFoundException;

/**
 * Class Drupal8.
 */
class Drupal8 extends OriginalDrupal8 implements CoreInterface {

  use UsersTrait;
  use CronTrait;
  use FileTrait;
  use EntityTrait;

  /**
   * {@inheritdoc}
   */
  public function pageCacheClear($path) {
    $this->cacheClear($path, 'page');
  }

  /**
   * {@inheritdoc}
   */
  public function cacheClear($cid, $bin = 'cache') {
    \Drupal::cache($bin)->delete($cid);
  }

  /**
   * {@inheritdoc}
   */
  public function staticEntityCacheClear($entity_type_id, array $ids = NULL) {
    \Drupal::entityTypeManager()->getStorage($entity_type_id)->resetCache($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function viewsCacheClear($view_name) {
    throw new PendingException('Views cache clearing not implemented yet in Drupal 8!');
  }

  /**
   * {@inheritdoc}
   */
  public function runElysiaCron() {
    throw new PendingException('Elysia cron run not implemented yet!');
  }

  /**
   * {@inheritdoc}
   */
  public function runElysiaCronJob($job) {
    throw new PendingException('Elysia job cron run not implemented yet!');
  }

  /**
   * {@inheritdoc}
   */
  public function runUltimateCron($cron_name) {
    if (!\Drupal::moduleHandler()->moduleExists('ultimate_cron')) {
      throw new \Exception("The Ultimate Cron module is not installed.");
    }

    $cron_job = current(\Drupal::entityTypeManager()->getStorage('ultimate_cron_job')->loadByProperties(['id' => $job]));
    if ($cron_job instanceof CronJob) {
      $cron_job->run(t('Run by behat Cron Context'));
    }
    else {
      throw new \InvalidArgumentException(sprintf("Could not find cron job with name: " . $job));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function loadUserByProperty($property, $value, $reset = TRUE) {
    return $this->loadEntityByProperties('user', [$property => $value]);
  }

  /**
   * Load an entity by label.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $label
   *   The label value.
   * @param bool $reset_cache
   *   Whether or not to reset the cache before loading the entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|mixed
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function loadEntityByLabel(string $entity_type, string $label, $reset_cache = FALSE) {
    if ($entity_type === 'user') {
      $label_key = 'name';
    }
    else {
      $label_key = \Drupal::entityTypeManager()
        ->getStorage($entity_type)
        ->getEntityType()
        ->getKey('label');
    }

    return $this->loadEntityByProperties($entity_type, [$label_key => $label], $reset_cache);
  }

  /**
   * {@inheritdoc}
   */
  public function loadEntityByProperties(string $entity_type, array $properties, $reset_cache = FALSE) {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()
      ->getStorage($entity_type);
    $entity_query = $storage->getQuery();
    $entity_query->accessCheck(FALSE);
    foreach ($properties as $name => $value) {
      // Cast scalars to array so we can consistently use an IN condition.
      $entity_query->condition($name, (array) $value, 'IN');
    }
    $result = $entity_query->execute();
    if (empty($result)) {
      return NULL;
    }

    if ($reset_cache) {
      $storage->resetCache($result);
    }

    $entities = $storage->loadMultiple($result);
    if (!empty($entities)) {
      $entity = current($entities);
      if ($entity instanceof EntityInterface) {
        return $entity;
      }
    }
  }

  /**
   * Load the latest entity of a given type.
   *
   * @param string $entity_type
   *   The entity type to search.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   */
  public function loadLatestEntity(string $entity_type) {
    return $this->loadLatestEntityByProperties($entity_type);
  }

  /**
   * Load the latest entity of a given type filtered by properties.
   *
   * @param string $entity_type
   *   The entity type to search.
   * @param array $properties
   *   The properties to search for.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function loadLatestEntityByProperties(string $entity_type, array $properties = []) {
    $storage = \Drupal::entityTypeManager()->getStorage($entity_type);
    $query = $storage->getQuery();

    foreach ($properties as $property => $value) {
      $query->condition($property, $value);
    }

    $query->sort('created', 'DESC');
    $query->range(0, 1);

    $results = $query->execute();
    if (!empty($results)) {
      $id = current($results);
      return \Drupal::entityTypeManager()->getStorage($entity_type)->load($id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getUserRoles($user) {
    return $user->getRoles();
  }

  /**
   * Discovers last entity id created of type.
   */
  public function getLastEntityId($entity_type, $bundle = NULL) {

    $info = \Drupal::entityTypeManager()->getDefinition($entity_type);
    $id_key = $info->getKey('id');
    $bundle_key = $info->getKey('bundle');

    $query = \Drupal::entityQuery($entity_type);
    if ($bundle) {
      $query->condition($bundle_key, $bundle);
    }
    $query->sort($id_key, 'DESC');
    $query->range(0, 1);
    $query->addMetaData('account', \Drupal::entityTypeManager()->getStorage('user')->load(1));
    $results = $query->execute();

    if (!empty($results)) {
      $id = reset($results);
      return $id;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function entityUri($entity_type, $entity, $route = 'canonical') {
    switch($route) {
      case 'edit':
      case 'delete':
      case 'add':
        $rel = sprintf('%s-form', $route) ;
        break;
      default:
        $rel = $route;
    }
    return $entity->hasLinkTemplate($rel) ? $entity->toUrl($rel)->getInternalPath() :  $entity->toUrl()->getInternalPath() . '/' . $route;
  }

  /**
   * {@inheritdoc}
   */
  public function entityLoadSingle($entity_type, $id) {
    $controller = \Drupal::entityTypeManager()->getStorage($entity_type);
    $entity = $controller->load($id);
    Assert::notEq($entity, FALSE, 'Entity of type "' . $entity_type . '" with id "' . $id . '" does not exists.');
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function attachParagraphToEntity($paragraph_type, $paragraph_field, array $paragraph_values, $entity, $entity_type) {
    $paragraph_values['type'] = $paragraph_type;
    $paragraph = Paragraph::create($paragraph_values);
    $paragraph->save();
    $entity->get($paragraph_field)->appendItem($paragraph);
    return $paragraph;
  }

  /**
   * {@inheritdoc}
   */
  public function entityId($entity_type, $entity) {
    return $entity->id();
  }

  /**
   * {@inheritdoc}
   */
  public function entitySave($entity_type, $entity) {
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function nodeAccessAcquireGrants($node) {
    throw new PendingException('Node access grants not implemented yet!');
  }

  /**
   * {@inheritdoc}
   */
  public function getFileDestination($filename, $directory) {
    $public = 'public://';
    $private = 'private://';

    if (empty($directory) || strpos($directory, $public) !== FALSE) {
      $realpath = \Drupal::service('file_system')->realpath($directory);
      $path = str_replace(DRUPAL_ROOT, '', $realpath);
      $destination = $path . '/' . basename($filename);
    }

    if (!empty($directory) && strpos($directory, $private) !== FALSE) {
      $path = str_replace($private, '', $directory);
      $destination = \Drupal\Core\Url::fromRoute('system.private_file_download', ['filepath' => $path . '/' . $filename], [
          'relative' => TRUE,
        ])->toString();
    }

    return (!empty($destination)) ? $destination : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function fileDelete($fid) {
    \Drupal::entityTypeManager()->getStorage('file')->load($fid)->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityByField($entity_type, $field_name, $value) {
    $storage = \Drupal::entityTypeManager()->getStorage($entity_type);
    $entities = $storage
      ->loadByProperties([$field_name => $value]);
    $entity = !empty($entities) ? end($entities) : NULL;
    return $entity instanceof EntityInterface ? $storage->loadUnchanged($entity->id()) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFieldValue($field, $entity, $fallback = NULL) {
    if ($entity->hasField($field)) {
      $fallback = ($field == 'roles') ? explode(', ', $entity->get($field)->getString()) : $entity->get($field)->getString();
    }
    return $fallback;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypes() {
    return array_keys(\Drupal::entityTypeManager()->getDefinitions());
  }

  /**
   * {@inheritdoc}
   */
  public function getEntitiesWithCondition($entity_type, $condition_key, $condition_value, $condition_operand = 'LIKE') {
    $database = \Drupal::database();
    $query = \Drupal::entityQuery($entity_type);
    $condition_scaped = strtoupper($condition_operand) == 'LIKE' ? '%' . $database->escapeLike($condition_value) . '%' : $condition_value;
    $query->condition($condition_key, $condition_scaped, $condition_operand);
    $query->accessCheck(FALSE);
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function entityDelete($entity_type, $entity_id, $reset_cache = FALSE, $throw_exception = TRUE) {
    if ($entity_id instanceof EntityInterface) {
      $entity_id = $entity_id->id();
    }
    $controller = \Drupal::entityTypeManager()->getStorage($entity_type);

    if ($reset_cache) {
      $controller->resetCache([$entity_id]);
    }

    $entity = $controller->load($entity_id);
    if ($entity instanceof EntityInterface) {
      $entity->delete();
    }
    elseif ($throw_exception) {
      throw new EntityNotFoundException($entity_type, $entity_id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function entityDeleteMultiple($entity_type, array $entities_ids, $reset_cache = FALSE) {
    $controller = \Drupal::entityTypeManager()->getStorage($entity_type);
    $entities = $controller->loadMultiple($entities_ids);

    if ($reset_cache) {
      $controller->resetCache($entities_ids);
    }

    $controller->delete($entities);
  }

  /**
   * {@inheritdoc}
   */
  public function getDbLogMessages(int $scenario_start_time, array $severities = [], array $types = []) {
    $query = \Drupal::database()->select('watchdog', 'w')
      ->fields('w', ['message', 'variables', 'type', 'wid'])
      ->condition('timestamp', $scenario_start_time, '>=');

    if (!empty($severities)) {
      $query->condition('severity', $severities, 'IN');
    }

    if (!empty($types)) {
      $query->condition('type', $types, 'IN');
    }

    return $query->execute()->fetchAll();
  }

  /**
   * {@inheritdoc}
   */
  public function formatString($string, array $params) {
    $string = new FormattableMarkup($string, $params);
    return $string;
  }

  /**
   * {@inheritdoc}
   */
  public function getState($key) {
    return \Drupal::state()->get($key);
  }

  /**
   * {@inheritdoc}
   */
  public function setState($key, $value) {
    \Drupal::state()->set($key, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function validMail($email_address) {
    return \Drupal::service('email.validator')->isValid($email_address);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultFileScheme() {
    return \Drupal::config('system.file')
      ->get('default_scheme') . '://';
  }

  /**
   * Gets the current Honeypot time limit
   *
   * @return int
   *   The time limit value
   */
  public function getHoneypotLimit(): int {
    return \Drupal::configFactory()->getEditable('honeypot.settings')->get('time_limit');
  }

  /**
   * Sets the Honeypot time limit
   *
   * @param int $time_limit
   *   The time limit to be set.
   */
  public function setHoneypotLimit(int $time_limit) {
    \Drupal::configFactory()->getEditable('honeypot.settings')->set('time_limit', $time_limit)->save();
  }

  /**
   * {@inheritdoc}
   */
  public function createFileUrl($file, bool $relative = TRUE) {
    if ($file instanceof FileInterface) {
      return $file->createFileUrl($relative);
    }
    throw new InvalidArgumentException('%s method only accept %s objects in Drupal 8 or higher', __METHOD__, FileInterface::class);
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguagePrefix($language) {
    $language_manager = \Drupal::languageManager();
    $language_list = $language_manager->getStandardLanguageList();

    $filter_func = function ($item) use ($language) {
      return in_array($language, $item);
    };

    $found = array_filter($language_list, $filter_func);

    if (empty($found)) {
      throw new \InvalidArgumentException(sprintf("Language %s not found", $language));
    }

    $prefixes = \Drupal::config('language.negotiation')->get('url.prefixes');
    return $prefixes[array_key_first($found)];
  }

}
