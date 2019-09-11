<?php

namespace Metadrop\Behat\Context;

use Behat\Gherkin\Node\TableNode;

class ParagraphsContext extends RawDrupalContext {

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
   * @param string $paragraph_field
   *   Field to reference the paragrapshs.
   * @param \Behat\Gherkin\Node\TableNode $paragraph_fields_table
   *   Paragraph fields.
   *
   * @Given paragraph of :paragraph_type type referenced on the :field_paragraph field of the last content:
   */
  public function createParagraph($paragraph_type, $paragraph_field, TableNode $paragraph_fields_table) {
    $entity_type = 'node';
    $last_id = $this->getCore()->getLastEntityId($entity_type);
    if (empty($last_id)) {
      throw new \Exception("Impossible to get the last content id.");
    }

    $entity = $this->getCore()->entityLoadSingle($entity_type, $last_id);

    // Create multiple paragraphs.
    foreach ($paragraph_fields_table->getHash() as $paragraph_data) {
      $paragraph_object = (object) $paragraph_data;
      $paragraph_object->type = $paragraph_type;
      $this->parseEntityFields('paragraph', $paragraph_object);
      $this->expandEntityFields('paragraph', $paragraph_object);
      $this->getCore()->attachParagraphToEntity($paragraph_type, $paragraph_field, (array) $paragraph_object, $entity, $entity_type);
    }
    $this->getCore()->entitySave($entity_type, $entity);
  }

}
