<?php

/**
 * @file
 *
 * DrupalUtilsContext Context for Behat.
 *
 * Adds steps for UI elements.
 *
 */

namespace Metadrop\Behat\Context;

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Mink\Exception\ElementNotFoundException;
use Drupal\DrupalExtension\Context\RawDrupalContext;

class UIContext extends RawDrupalContext implements SnippetAcceptingContext {

  /**
   * Step to fill a Chosen select form element.
   *
   * It doesn't work when multiple selection is enabled.
   *
   * See https://harvesthq.github.io/chosen/
   *
   * @Given I select :option from :select chosen.js select box
   */
  public function iSelectFromChosenJsSelectBox($option, $select) {

    // Get field.
    $page = $this->getSession()->getPage();
    print_r($select);
    $field = $page->findField($select, true);
    if (NULL === $field) {
      throw new ElementNotFoundException($this->getSession(), 'form field', 'id|name|label|value', $select);
    }

    // Get option.
    $id = $field->getAttribute('id');
    $opt = $field->find('named', array('option', "'" . $option . "'"));
    if (NULL === $opt) {
      throw new ElementNotFoundException($this->getSession()->getDriver(), 'form field select option', 'id|name|label|value', $opt);
    }

    // Build JS code to select given option.
    $val = $opt->getValue();
    $javascript = "jQuery('#$id').val('$val');
                   jQuery('#$id').trigger('chosen:updated');
                   jQuery('#$id').trigger('change');";
    $this->getSession()->executeScript($javascript);
  }
}
