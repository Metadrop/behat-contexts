<?php

/**
 * @file
 *
 * DrupalExtendedContext Context for Behat.
 *
 */

namespace Metadrop\Behat\Context;

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\TableNode;
use NuvoleWeb\Drupal\DrupalExtension\Context\RawDrupalContext;

class DrupalExtendedContext extends RawDrupalContext implements SnippetAcceptingContext {

 /**
   * Array of files to be cleaned up @AfterScenario.
   *
   * @var array
   */
  protected $files = array();

  /**
   * Gets info about required state of a form element.
   *
   * It relies on the requeried class added to he element by Drupal. This
   * approach doesn't work with file type input elements.
   *
   * @param string $label
   *   Form element label.
   * @param string $type
   *   Form element type.
   * @throws \InvalidArgumentException
   */
  protected function isFormElementRequired($type, $label) {
    if ($label === 'file') {
      throw new \InvalidArgumentException("Form element \"file\" type not supported");
    }

    $page = $this->getSession()->getPage();

    // Try to find element.
    $xpath_element = "//label[contains(text(), '{$label}')]/..//{$type}";
    $element = $page->find('xpath', $xpath_element);
    if (NULL === $element) {
      throw new \InvalidArgumentException("Could not find the form element \"$label\" of type \"$type\"");
    }

    // Check required class.
    $xpath_required = "//label[contains(text(), '{$label}')]/..//{$type}[contains(@class, 'required')]";
    $element_required = $page->find('xpath', $xpath_required);

    return NULL !== $element_required;
  }

  /**
   * Flush page cache.
   *
   * @param string $path
   *  Page name without first "/"
   *  Use "*" as wildcard. Example: articles/*
   *
   * @Given :path page cache is flushed
   */
  public function pageCacheIsFlushed($path = NULL) {
    global $base_url;

    if (!empty($path) && $path !== '*') {
      $path = $base_url . '/' . $path;
    }

    $this->getCore()->cacheClear($path, 'page');
  }

  /**
   * Flush views data cache.
   *
   * @param string $view_name
   *  Views name
   *
   * @Given :view view data cache is flushed
   */
  public function viewDataCacheIsFlushed($view_name) {
    $this->getCore()->viewsCacheClear($view_name);
  }

  /**
   * @Given I run elysia cron
   *
   * Run elysia-cron.
   */
  public function iRunElysiaCron() {
    $this->getCore()->runElysiaCron();
  }

  /**
   * @Given I run the elysia cron :job job
   *
   * Run elysia-cron-job.
   */
  public function iRunElysiaCronJob($job) {
    // @NOTE We force it
    $this->getCore()->runElysiaCronJob($job);
  }

  /**
   * @Given I run the cron of Search API
   *
   * Run search-api-cron
   */
  public function iRunTheCronOfSearchApi() {
    $this->getCore()->runModuleCron('search_api');
  }

  /**
   * @Given I run the cron of Search API Solr
   *
   * Run search-api-solr-cron
   */
  public function iRunTheCronOfSearchApiSolr() {
    $this->getCore()->runModuleCron('search_api_solr');
  }

  /**
   * Check the user has or not a specific role.
   *
   * @param string $role
   *   Role name(s) separated by comma.
   * @param string $user
   *   User identifier: username | mail | uid or NULL to current user.
   * @param bool $not
   *   True if the user should NOT have the specific roles.
   */
  public function userRoleCheck($role, $user = NULL, $not = FALSE) {
    if (empty($user)) {
      $current_user = $this->getUserManager()->getCurrentUser();
      $account = $this->getCore()->loadUserByProperty('uid', $current_user->uid);
    }
    else {
      $property = $this->getCore()->getUserPropertyByName($user);
      $account = $this->getCore()->loadUserByProperty($property, $user);
    }

    if ($account) {
      $roles = explode(',', $role);
      $roles = array_map('trim', $roles);
      // Case insensitive:
      $roles = array_map('strtolower', $roles);
      $aroles = array_map('strtolower', $this->getCore()->getUserRoles($account));
      foreach ($roles as $role) {
        if (!$not && !in_array($role, $aroles)) {
          throw new \Exception("Given user does not have the role $role");
        }
        else if ($not && in_array($role, $aroles)) {
          throw new \Exception("Given user have the role $role");
        }
      }
    }
    else {
      throw new \Exception("Given user does not exists!");
    }
  }

  /**
   * Check the user has a specific role.
   *
   * @see userRoleCheck()
   *
   * @Then I should have the :role role(s)
   * @Then the user :user should have the :role role(s)
   */
  public function userShouldHaveTheRole($role, $user = NULL) {
    return $this->userRoleCheck($role, $user);
  }

  /**
   * Check the user does not have a specific role.
   *
   * @see userRoleCheck()
   *
   * @Then I should not have the :role role(s)
   * @Then the user :user should not have the :role role(s)
   */
  public function userShouldNotHaveTheRole($role, $user = NULL) {
    return $this->userRoleCheck($role, $user, TRUE);
  }

  /**
   * Checks if a form element is required.
   *
   *
   * @Then form :type element :label should be required
   */
  public function formElementShouldBeRequired($type, $label) {
    if (!$this->isFormElementRequired($type, $label)) {
      throw new \InvalidArgumentException("Form element \"$label\" of type \"$type\" is not required");
    }
  }

  /**
   * Checks if a form element is not required.
   *
   * @Then form :type element :label should not be required
   */
  public function formElementShouldNotBeRequired($type, $label) {
    if ($this->isFormElementRequired($type, $label)) {
      throw new \InvalidArgumentException("Form element \"$label\" of type \"$type\" is required");
    }
  }

  /**
   * Get last entity id created
   *
   * @param string $entity_type
   *   Entity type
   * @param string $bundle
   *   Entity bundle
   *
   * @return integer
   *   Entity Id
   */
  public function getLastEntityId($entity_type, $bundle = NULL) {

    $info = entity_get_info($entity_type);
    $id_key = $info['entity keys']['id'];

    $query = new \EntityFieldQuery();
    $query->entityCondition('entity_type', $entity_type);
    if ($bundle) {
      $query->entityCondition('bundle', $bundle);
    }

    $query->propertyOrderBy($id_key, 'DESC');
    $query->range(0, 1);
    $query->addMetaData('account', user_load(1));

    $result = $query->execute();
    $keys = array_keys($result[$entity_type]);
    $id = reset($keys);

    if (empty($id)){
      throw new \Exception("Can't take last one");
    }

    return $id;
  }

  /**
   * Discovers last entity id created of type.
   */
  public function getLastEntityIdD8($entity_type, $bundle = NULL) {

    $info = \Drupal::entityTypeManager()->getDefinition($entity_type);
    $id_key = $info->getKey('id');
    $bundle_key = $info->getKey('bundle');

    $query = \Drupal::entityQuery($entity_type);
    if ($bundle) {
      $query->condition($bundle_key, $bundle);
    }
    $query->sort($id_key, 'DESC');
    $query->range(0, 1);
    $query->addMetaData('account', user_load(1));
    $results = $query->execute();

    if (!empty($results)) {
      $id = reset($results);
      return $id;
    }
  }

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
    $last_entity = $this->getLastEntityId($entity_type, $bundle);
    if (empty($last_entity)) {
      throw new \Exception("Imposible to go to path: the entity does not exists");
    }

    $entity = entity_load_single($entity_type, $last_entity);
    if (!empty($entity)) {
      $uri = entity_uri($entity_type, $entity);
      $path = empty($subpath) ? $uri['path'] : $uri['path'] . '/' . $subpath;
      $this->getSession()->visit($this->locatePath($path));
    }
  }

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
    $lastNodeId = $this->getLastEntityId('node', $bundle);
    if (empty($lastNodeId)) {
      throw new \Exception("Can't get last node");
    }

    $node = node_load($lastNodeId);
    node_access_acquire_grants($node);

  }

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

  /**
   * Deletes Files after each Scenario.
   *
   * @AfterScenario
   */
  public function cleanFiles() {
    foreach ($this->files as $k => $v) {
      file_delete($v);
    }
  }

  /**
   * Creates file in drupal.
   *
   * @param string $filename
   *   The name of the file to create.
   * @param string directory
   *   A string containing the files scheme, usually "public://".
   *
   * @throws Exception
   *   Exception file not found.
   *
   * @throws Exception
   *   Exception file could not be copied.
   *
   * @USECORE
   *
   * @Given file with name :filename
   * @Given file with name :filename in the :directory directory
   */
  public function createFileWithName($filename, $directory = NULL) {

    if (empty($directory)) {
      $directory = file_default_scheme() . '://';
    }

    $destination = $directory . '/' . $filename;

    $absolutePath = $this->getMinkParameter('files_path');
    $path = $absolutePath . '/' . $filename;

    if (!file_exists($path)) {
      throw new \Exception("Error: file " . $filename ." not found");
    }
    else {
      $data = file_get_contents($path);
      $file = file_save_data($data, $destination, FILE_EXISTS_REPLACE);
      if ($file) {
        $this->files[] = $file;
      }
      else {
        throw new \Exception("Error: file could not be copied to directory");
      }
    }
  }

  /**
   * Wait for AJAX to finish.
   *
   * @param int $seconds
   *   Max time to wait for AJAX.
   *
   * @Given I wait for AJAX to finish at least :seconds seconds
   *
   * @throws \Exception
   *   Ajax call didn't finish on time.
   */
  public function iWaitForAjaxToFinish($seconds) {
    $finished = $this->getSession()->wait($seconds * 1000, '(typeof(jQuery)=="undefined" || (0 === jQuery.active && 0 === jQuery(\':animated\').length))');
    if (!$finished) {
      throw new \Exception("Ajax call didn't finished within $seconds seconds.");
    }
  }

  /**
   * Wait for batch process.
   *
   * Wait until the id="updateprogress" element is gone,
   * or timeout after 5 seconds (5,000 ms).
   *
   * @param init $seconds
   *
   * @Given I wait for the batch job to finish
   * @Given I wait for the batch job to finish at least :seconds seconds
   */
  public function iWaitForTheBatchJobToFinish($seconds = 5) {
    $this->getSession()->wait($seconds * 1000, 'jQuery("#updateprogress").length === 0');
   }

  /**
   * Check param with value in url.
   *
   * @Then current url should have the ":param" param with ":value" value
   */
  public function urlShouldHaveParamWithValue($param, $value, $have = TRUE) {
    $url = $this->getSession()->getCurrentUrl();
    $queries = [];
    parse_str(parse_url($url, PHP_URL_QUERY), $queries);
    if (!(isset($queries[$param]) && $queries[$param] == $value) && $have) {
      throw new \Exception("The param " . $param . " with value " . $value . " is not in the url");
    }
    elseif (isset($queries[$param]) && $queries[$param] == $value && !$have) {
      throw new \Exception("The param " . $param . " with value " . $value . " is in the url");
    }
  }

  /**
   * Check param with value in url not exists.
   *
   * @Then current url should not have the ":param" param with ":value" value
   */
  public function urlShouldNotHaveParamWithValue($param, $value) {
    $this->urlShouldHaveParamWithValue($param, $value, FALSE);
  }

   /**
    * Check that user with mail exists.
    *
    * @Then user with mail :mail exists
    */
   public function userWithMailExists($mail, $exists = TRUE) {
     $user = user_load_by_mail($mail);
     if (!$user && $exists) {
       throw new \Exception("The user with mail '" . $mail . "' was not found.");
     }
     elseif (!empty($user) && !$exists) {
       throw new \Exception("The user with mail '" . $mail . "' exists.");
     }
   }

   /**
    * Check that user with mail not exists.
    *
    * @Then user with mail :mail not exists
    */
   public function userWithMailNotExists($mail) {
     $this->userWithMailExists($mail, FALSE);
   }

  /**
   * Overrides \Drupal\Driver\Cores\AbstractCore::expandEntityFields method.
   *
   * That method is protected and we can't use it from this context.
   */
  protected function expandEntityFields($entity_type, \stdClass $entity) {
    $field_types = $this->getCore()->getEntityFieldTypes($entity_type);
    foreach ($field_types as $field_name => $type) {
      if (isset($entity->$field_name)) {
        $entity->$field_name = $this->getCore()->getFieldHandler($entity, $entity_type, $field_name)
          ->expand($entity->$field_name);
      }
    }
  }

  /**
   * Checks that text appears before another text.
   *
   * Example:
   * I should see "Element 1" text before "Element 2" in the "content" region.
   *
   * @param string $first_text
   *   First text.
   * @param string $second_text
   *   Second text.
   * @param string $region
   *   Region.
   *
   * @Then I should see :text1 text before :text2
   * @Then I should see :text1 text before :text2 in the :region region
   */
  public function iShouldSeeTextBefore($first_text, $second_text, $region = NULL) {
    // Take sesion & page.
    $session = $this->getSession();
    if (isset($region)) {
      $page = $session->getPage()->find('region', $region);
    }
    else {
      $page = $session->getPage();
    }

    $xpath = "//*[contains(*, '$second_text')]/preceding::*[contains(*, '$first_text')]";
    $element = $page->find('xpath', $xpath);
    // Check.
    if ($element === NULL) {
      throw new \Exception("The text $first_text is not being seen before $second_text");
    }
  }

  /**
   * Create a paragraph and reference it in the given field of the last node created.
   *
   * @USECORE
   *
   * Only works in drupal 8.
   * You can only create several paragraphs of the same type at once.
   * To add other types you must do so in different steps.
   *
   * Example:
   * Given paragraph of "paragraph_type" type referenced on the "field_paragraph" field of the last content:
   *  | title                  | field_body        |
   *  | Behat paragraph        | behat body        |
   *  | Behat paragraph Second | behat second body |
   *
   * Given paragraph of "paragraph_type_second" type referenced on the "field_paragraph" field of the last content:
   *  | title                  | field_text        |
   *  | Behat paragraph        | behat text        |
   *  | Behat paragraph Second | behat second text |
   *
   * @param string $paragraph_type
   *   Paragraph type.
   * @param string $field_paragraph
   *   Field to reference the paragrapshs.
   * @param \Behat\Gherkin\Node\TableNode $paragraph_fields_table
   *   Paragraph fields.
   *
   * @Given paragraph of :paragraph_type type referenced on the :field_paragraph field of the last content:
   */
  public function createParagraph($paragraph_type, $field_paragraph, TableNode $paragraph_fields_table) {
    $entity_type = 'node';
    $last_id = $this->getLastEntityIdD8($entity_type);
    if (empty($last_id)) {
      throw new \Exception("Impossible to get the last content id.");
    }

    $controller = \Drupal::entityManager()->getStorage($entity_type);
    $entity = $controller->load($last_id);

    // Create multiple paragraphs.
    foreach ($paragraph_fields_table->getHash() as $paragraph_data) {
      $paragraph_object = (object) $paragraph_data;
      $paragraph_object->type = $paragraph_type;
      $this->parseEntityFields('paragraph', $paragraph_object);
      $this->expandEntityFields('paragraph', $paragraph_object);
      $paragraph = Paragraph::create((array) $paragraph_object);
      $paragraph->save();
      $entity->get($field_paragraph)->appendItem($paragraph);
    }
    $entity->save();
  }

}
