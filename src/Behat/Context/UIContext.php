<?php

namespace Metadrop\Behat\Context;

use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use NuvoleWeb\Drupal\DrupalExtension\Context\RawMinkContext;
use Behat\Mink\Exception\ExpectationException;
use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use NuvoleWeb\Drupal\DrupalExtension\Context\ScreenShotContext;

/**
 * @file
 * DrupalUtilsContext Context for Behat.
 */

/**
 * Adds steps for UI elements.
 */
class UIContext extends RawMinkContext {

  /**
   * Context parameters.
   *
   * @var array
   */
  protected $customParams;

  /**
   * @var \Metadrop\Behat\Context\WaitingContext
   */
  protected $waitingContext;

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
   * Step to fill a Chosen select form element.
   *
   * @TODO: compare method with nuvole drupal-extension, drop from here if
   * nuvole method works!
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
   * Fill in a select 2 autocomplete field.
   *
   * @When I fill in the select2 autocomplete :autocomplete with :text
   */
  public function fillInSelect2Autocomplete($locator, $text) {

    $session = $this->getSession();
    $xpath = "//*[@name=\"$locator\"]/following-sibling::*//input[contains(@class, 'select2-search__field')]";

    $el = $session->getPage()->find('xpath', $xpath);

    if (empty($el)) {
      throw new ExpectationException('No such autocomplete element ' . $locator, $session);
    }

    // Set the text and trigger the autocomplete with a space keystroke.
    $el->setValue($text);

    try {
      $el->keyDown(' ');
      $el->keyUp(' ');

      // Wait for ajax.
      $this->getSession()->wait(1000, '(typeof(jQuery)=="undefined" || (0 === jQuery.active && 0 === jQuery(\':animated\').length))');
      // Wait a second, just to be sure.
      sleep(1);

      // Select the autocomplete popup with the name we are looking for.
      $popup = $session->getPage()->find('xpath', "//ul[contains(@class, 'select2-results__options')]/li[text() = '{$text}']");

      if (empty($popup)) {
        throw new ExpectationException('No such option ' . $text . ' in ' . $locator, $session);
      }

      // Clicking on the popup fills the autocomplete properly.
      $popup->click();
    }
    catch (UnsupportedDriverActionException $e) {
      // So javascript is not supported.
      // We did set the value correctly, so Drupal will figure it out.
    }

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
    // Added waiting for scroll. Default is half a second, but it can
    // be overriden in UIContext.
    $scroll_wait_seconds = $this->customParams['scroll_time'] ?? 0.5;
    usleep($scroll_wait_seconds * pow(10, 6));
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

  /**
   * Gather neccesary subcontexts.
   *
   * @BeforeScenario
   *
   * @param BeforeScenarioScope $scope
   *   Scope del scenario.
   */
  public function gatherWaitingContext(BeforeScenarioScope $scope) {
    /** @var InitializedContextEnvironment $environment */
    $environment = $scope->getEnvironment();
    if ($environment->hasContextClass(WaitingContext::class)) {
      $this->waitingContext = $environment->getContext(WaitingContext::class);
    }
  }

  /**
   * Fill a composed multiple field.
   *
   * @NOTE: This step has a strong dependency with the markup of the drupal base
   * multiple fields, if the html is modified it may stop working.
   *
   * @Then I fill in multiple field :arg1 with the following values:
   */
  public function iFillInMultipleFieldWithTheFollowingValues($field_label, TableNode $table)
  {
    $xpath_base = sprintf('//table[contains(@class, "field-multiple-table")]/thead[contains(*, "%s")]/../tbody', $field_label);
    $xpath_add_more_button = sprintf($xpath_base . '/../..//input[@type="submit"][contains(@class, "field-add-more-submit")]');
    $field_items = iterator_to_array($table->getIterator());
    $total_items = count($field_items);

    if ($total_items > 1 && empty($this->waitingContext)) {
      trigger_error(sprintf('In order to add multiple field items you need add %s context in your behat configuration.', WaitingContext::class));
    }

    foreach ($field_items as $delta =>  $field_item) {
      $item_number = $delta + 1;
      foreach ($field_item as $label => $value) {
        $xpath_field_item = sprintf($xpath_base . '/tr[%d]/td//label[text()="%s"]/../*[self::input or self::select]', $item_number, $label);
        $element = $this->getSession()->getPage()->find(
          'xpath',
          $xpath_field_item
        );

        if (empty($element)) {
          throw new \Exception(sprintf('Cant find field "%s" in %s field at delta %s', $label, $field_label, $item_number));
        }

        $this->fillFieldUnknown($element, $value);
      }

      if ($item_number < $total_items) {
        $add_more_button = $this->getSession()->getPage()->find('xpath', $xpath_add_more_button);
        if (!empty($add_more_button)) {
          $add_more_button->press();
        }
        else {
          throw new \Exception("Can't find add more button.");
        }
        $this->waitingContext->iWaitForAjaxToFinish(5);
      }

    }
  }

  /**
   * Set a value in a input in an unknown select field.
   *
   * Detect on the fly the type of input so it call the appropiate methods.
   *
   * @param NodeElement $element
   *   Element.
   * @param $value
   *   Value.
   *
   * @throws ElementNotFoundException
   */
  protected function fillFieldUnknown(NodeElement $element, $value) {
    switch ($element->getTagName()) {
      case 'input':
        switch ($element->getAttribute('type')) {
          case 'checkbox':
            $value == 1 ? $element->check() : $element->uncheck();
            break 2;
          case 'radio':
            if ($value == 1) {
              $value = $element->getAttribute('value');
              $element->selectOption($value, FALSE);
            }
            break 2;
        }

      default:
        $element->setValue($value);

    }
  }

  /**
   * Check selector is disabled or not.
   *
   * @Then the input with :label label should be disabled
   */
  public function inputIsDisabled($label) {
    $session = $this->getSession();
    $xpath = "//input[@id=//label[contains(text(),'" . $label . "')]/@for]";
    $element = $session->getPage()->find(
      'xpath',
      $xpath
    );
    if (empty($element) || !($element instanceof NodeElement)) {
      throw new \Exception("The input with label {$label} was not found");
    }
    $disabled =  $element->getAttribute('disabled');
    if (is_null($disabled) || $disabled != "disabled") {
      throw new \Exception("The input with label {$label} does not have disabled attribute.");
    }
  }

  /**
   * Checks the position of an item on a list.
   *
   * @param string $item_class_selector
   *   Item list class selector.
   * @param string $item_label
   *   Label to find the item on the list.
   * @param string $list_wrapper_class_selector
   *   Parent wrapper to find into.
   * @param int $item_position
   *   Item position.
   *
   * @Note This method is an internal function,
   * create a more human-readable step to use on your test.
   * Example of use on method theCardWithTitleShouldBeInPositionExample().
   */
  public function elementShouldBeInPosition(string $item_class_selector, string $item_label, string $list_wrapper_class_selector, int $item_position) {
    $session = $this->getSession();
    $locator = "//div[contains(@class, '$list_wrapper_class_selector')]/div[contains(@class, '$item_class_selector')][$item_position]//*[contains(string(), '$item_label')]";
    $element_find = $session->getPage()->find('xpath', $locator);
    if (NULL === $element_find) {
      throw new \InvalidArgumentException(sprintf('Element with label "%s" could not be found or is not in the right position.', $item_label));
    }
  }

}
