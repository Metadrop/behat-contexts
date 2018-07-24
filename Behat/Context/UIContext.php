<?php

namespace Metadrop\Behat\Context;

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Mink\Exception\ElementNotFoundException;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Behat\Mink\Exception\ExpectationException;

/**
 * @file
 * DrupalUtilsContext Context for Behat.
 */

/**
 * Adds steps for UI elements.
 */
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
   * @param array|null $parameters
   *   Custom Parameters.
   */
  public function __construct($parameters = array()) {
    $this->customParams = $parameters;
  }

  /**
   * Step fill CKEditor.
   *
   * @Then I fill in CKEditor on field :locator with :value
   */
  public function iFillInCkEditorOnFieldWith($locator, $value) {
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
    $field = $page->findField($select, TRUE);
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
   *   jQuery selector.
   * @param int $offset
   *   Pixels to add or remove to selector position.
   *   E.G. Take into account fix headers, footers, etc.
   */
  public function scrollToSelector($selector, $offset = NULL) {
    $offset_default = isset($this->customParams['scroll_offset']) ? $this->customParams['scroll_offset'] : 0;
    $offset = is_null($offset) ? $offset_default : $offset;
    $op = $offset >= 0 ? '+' : '-';
    $script = "jQuery('html,body').unbind().animate({scrollTop: jQuery('$selector').offset().top" . $op . abs($offset) . "},0)";
    $this->getSession()->executeScript($script);
    // Added waiting for scroll.
    usleep(1000);
  }

  /**
   * Step scroll to selector.
   *
   * @When I scroll to :selector
   * @When I scroll to :selector with :offset
   */
  public function scrollToElement($selector, $offset = NULL) {
    $this->scrollToSelector($selector, $offset);
  }

  /**
   * Step scroll to field.
   *
   * @When I scroll to :field field
   * @When I scroll to :field field with :offset
   */
  public function scrollToField($field, $offset = NULL) {

    $page = $this->getSession()->getPage();
    $field = $page->findField($field, TRUE);

    if (NULL === $field) {
      throw new ElementNotFoundException($this->getSession(), 'form field', 'id|name|label|value', $field);
    }

    // Get option.
    $id = $field->getAttribute('id');
    $selector = '#' . $id;

    $this->scrollToSelector($selector, $offset);

  }

  /**
   * Click on the element with the provided CSS Selector.
   *
   * @When /^I click on the element with css selector "([^"]*)"$/
   */
  public function iClickOnTheElementWithCssSelector($cssSelector) {
    $session = $this->getSession();
    $element = $session->getPage()->find(
      'xpath',
      // Just changed xpath to css.
      $session->getSelectorsHandler()->selectorToXpath('css', $cssSelector)
    );
    if (NULL === $element) {
      throw new \InvalidArgumentException(sprintf('Could not evaluate CSS Selector: "%s"', $cssSelector));
    }

    $element->click();
  }

  /**
   * Click on the element with the provided xpath query.
   *
   * @When /^I click on the element with xpath "([^"]*)"$/
   */
  public function iClickOnTheElementWithXpath($xpath) {
    // Get the mink session.
    $session = $this->getSession();
    $element = $session->getPage()->find(
      'xpath',
      $session->getSelectorsHandler()->selectorToXpath('xpath', $xpath)
    );

    // Errors must not pass silently.
    if (NULL === $element) {
      throw new \InvalidArgumentException(sprintf('Could not evaluate XPath: "%s"', $xpath));
    }

    // ok, let's click on it.
    $element->click();
  }

  /**
   * Click on the label using xpath.
   *
   * @When I click on the :label label
   */
  public function iClickOnTheLabel($label) {
    $label = str_replace("\"", "\\\"", $label);
    $xpath = '//label[text()="' . $label . '"]';
    $this->iClickOnTheElementWithXPath($xpath);
  }

  /**
   * Get element by xpath.
   */
  protected function getElementByXpath($xpath_element) {
    $page = $this->getSession()->getPage();
    $findelement = $page->find('xpath', $xpath_element);
    return $findelement;
  }

  /**
   * Check if a type of element has an attribute with an specific value.
   *
   * @Then the :element element of :type type should have the :attribute attribute with :value value
   */
  public function theElementShouldHaveAttributeWithValue($element, $type, $attribute, $value, $not = FALSE) {
    $xpath_element = "//{$type}[contains(text(),'{$element}')]";
    $found_element = $this->getElementByXpath($xpath_element);

    if (is_null($found_element)) {
      throw new \Exception("The element {$element} was not found");
    }

    $xpath_attribute = $not ? "//{$type}[not(contains(@{$attribute},'{$value}'))][contains(text(),'{$element}')]" : "//{$type}[contains(@{$attribute},'{$value}')][contains(text(),'{$element}')]";

    $found_element_attribute = $this->getElementByXpath($xpath_attribute);

    if (is_null($found_element_attribute)) {
      $condition_error_string = $not ? "has" : "has not";
      throw new \Exception("The element {$element} {$condition_error_string} the attribute {$attribute} with the value {$value}");
    }
  }

  /**
   * Check if a type of element has an attribute with an specific value.
   *
   * @Then the :element element of :type type should not have the :attribute attribute with :value value
   */
  public function theElementShouldNotHaveAttributeWithValue($element, $type, $attribute, $value) {
    $this->theElementShouldHaveAttributeWithValue($element, $type, $attribute, $value, TRUE);
  }

  /**
   * Step switch to the frame.
   *
   * @When I switch to the frame :frame
   */
  public function iSwitchToTheFrame($frame) {
    $this->getSession()->switchToIFrame($frame);
    $this->iframe = $frame;
  }

  /**
   * Step switch out of all frames.
   *
   * @When I switch out of all frames
   */
  public function iSwitchOutOfAllFrames() {
    $this->getSession()->switchToIFrame();
    $this->iframe = NULL;
  }

}
