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
   * Context parameters.
   *
   * @var array
   */
  protected $customParams;

  /**
   * Constructor.
   *
   * @param type $parameters
   */
  public function __construct($parameters = array()) {
    $this->customParams = $parameters;
  }

  /**
   * @Then I fill in CKEditor on field :locator with :value
   */
  public function iFillInCKEditorOnFieldWith($locator, $value) {
    $el = $this->getSession()->getPage()->findField($locator);

    if (empty($el)) {
      throw new ExpectationException('Could not find CKEditor with locator: ' . $locator, $this->getSession());
    }

    $fieldId = $el->getAttribute('id');
    if (empty($fieldId)) {
      throw new Exception('Could not find an id for field with locator: ' . $locator);
    }

    $this->getSession()
      ->executeScript("CKEDITOR.instances[\"$fieldId\"].setData(\"$value\");");
  }

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

  /**
   * Step to remove the multiple property of a file field.
   *
   * PhantomJS is not compatible with file field multiple and crashes.
   * This workaround removes the property, this way the test can upload at least
   * one file to the widget.
   *
   * @Given the file field :field is not multiple
   */
  public function fileFieldIsNotMultiple($locator) {
    $el = $this->getSession()->getPage()->findField($locator);

    if (empty($el)) {
      throw new ExpectationException('Could not find element with locator: ' . $locator, $this->getSession());
    }

    $fieldId = $el->getAttribute('id');
    if (empty($fieldId)) {
      throw new Exception('Could not find an id for field with locator: ' . $locator);
    }

    $this->getSession()
      ->executeScript("jQuery('#$fieldId').removeAttr('multiple');");
  }

  /**
   * Helper to scroll to selector with JS.
   *
   * @param string $selector
   *   jQuery selector
   * @param int $offset
   *   Pixels to add or remove to selector position.
   *   E.G. Take into account fix headers, footers, etc.
   */
  public function scrollToSelector($selector, $offset = null) {
    $offset_default = isset($this->customParams['scroll_offset']) ? $this->customParams['scroll_offset'] : 0;
    $offset = is_null($offset) ? $offset_default : $offset;
    $op = $offset >= 0 ? '+' : '-';
    $script = "jQuery('html,body').unbind().animate({scrollTop: jQuery('$selector').offset().top" . $op . abs($offset) . "},'slow')";
    print $script;
    $this->getSession()->executeScript($script);
  }

  /**
   * @When I scroll to :selector
   * @When I scroll to :selector with :offset
   */
  public function scrollToElement($selector, $offset = null) {
    $this->scrollToSelector($selector, $offset);
  }

  /**
   * @When I scroll to :field field
   * @When I scroll to :field field with :offset
   */
  public function scrollToField($field, $offset = null) {

    $page = $this->getSession()->getPage();
    $field = $page->findField($field, true);

    if (NULL === $field) {
      throw new ElementNotFoundException($this->getSession(), 'form field', 'id|name|label|value', $field);
    }

    // Get option.
    $id = $field->getAttribute('id');
    $selector = '#' . $id;

    $this->scrollToSelector($selector, $offset);

  }

}
