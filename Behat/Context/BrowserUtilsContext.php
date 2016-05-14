<?php

/**
 * @file
 *
 * BrowserUtilsContext Context for Behat.
 *
 */

namespace Metadrop\Behat\Context;

use Behat\Behat\Context\SnippetAcceptingContext;
use Drupal\DrupalExtension\Context\RawDrupalContext;

class BrowserUtilsContext extends RawDrupalContext implements SnippetAcceptingContext {

  /**
   * @Then select :name should have the option :option selected
   *
   */
  public function selectShouldHaveTheOptionSelected($name, $option) {
    // Get the select element.
    $page = $this->getSession()->getPage();
    $element = $page->findField($name);

    if (NULL === $element) {
      throw new \InvalidArgumentException("Select with id|name|label \"$name\" not found.");
    }

    // Look for the given option.
    $option_elem = $element->find('named', array('option', $option));
    if (NULL === $option_elem) {
      throw new \InvalidArgumentException("Select \"$name\" doesn't have any option with key or value like \"$option\".");
    }

    if (!$option_elem->isSelected()) {
      throw new \InvalidArgumentException("Select \"$name\" doesn't have the option \"$option\" selected.");
    }
  }

  /**
   * @Then element :name should be disabled
   *
   */
  public function elementIsDisabled($name) {
    $session = $this->getSession();
    $element_disabled = $session->getPage()->find('xpath', '//*[contains(@value, "' . $name . '")][contains(@disabled, "disabled")]');
    $element = $session->getPage()->find('xpath', '//*[contains(@value, "' . $name . '")]');
    if ($element !== NULL && $element_disabled === NULL) {
      throw new Exception('Element "' . $name . '" is not disabled.');
    }
    elseif ($element === NULL) {
      throw new \InvalidArgumentException('Element not exists.');
    }
  }

  /**
   * Click on the element with the provided xpath query
   *
   * @When I click on the element with xpath :xpath
   *
   */
  public function iClickOnTheElementWithXPath($xpath) {
    $session = $this->getSession();
    $element = $session->getPage()->find('xpath', $session->getSelectorsHandler()->selectorToXpath('xpath', $xpath));
    if (NULL === $element) {
      throw new \InvalidArgumentException("XPath expression '$xpath' didn't match any element");
    }

    // Ok, let's click on it.
    $element->click();
  }

  /**
   * @Given I select the radio button with name :name
   *
   * This step is useful when a fancy radio buttons are used, for example
   * hiding the radio button so only the label is visible.
   *
   */
  public function iSelectTheRadioButtonWithName($name) {
    $session = $this->getSession();
    $element = $session->getPage()->find('xpath', "//label[contains(*, '{$name}')]/../input");

    $element = $this->findElementWithName($name);

    if (NULL === $element) {
      throw new \InvalidArgumentException("Could not find the input '$name'");
    }
    // Ok, let's click on it
    $element->click();
  }

  /**
   * Checks, that form element with specified label is visible on page.
   *
   * @NOTE: This is dependant of how the textfield is renderedd, it may not work
   * for certain themes of configurations (Fences module?).
   *
   * @Then /^(?:|I )should see a "(?P<text>(?:[^"]|\\")*)" textfield form element$/
   *
   */
  public function iShouldSeeATextfieldFormElement($label) {
    $page = $this->getSession()->getPage();
    $xpath = "//label[contains(*, '{$label}') or contains(text(), '{$label}')]/../input";
    $element = $page->find('xpath', $xpath);
    if ($element !== NULL) {
      if ($element->isVisible()) {
        return;
      }
      else {
        throw new \InvalidArgumentException("Textfield item with label \"$label\" is not visible.");
      }
    }
    else {
      throw new \InvalidArgumentException("Textfield item with label \"$label\" not found.");
    }
  }

  /**
   * Checks, that form element with specified label is visible on page.
   *
   * @see iShouldSeeATextfieldFormElement().
   *
   * @Then I should not see a :label textfield form element
   */
  public function iShouldNotSeeATextfieldFormElement($label) {
    try {
      $this->iShouldSeeATextfieldFormElement($label);
    } catch (\InvalidArgumentException $ex) {
      // Textfield is not visible or present, step is ok.
      return;
    }
    // Textfield was found inside the try-catch, throw error.
    throw new \InvalidArgumentException("Textfield with label \"$label\" is present and visible.");
  }

  /**
   * Checks, that select list element with specified label is visible on page.
   *
   * @Then I should see a :label select list form element
   */
  public function iShouldSeeASelectListFormElement($label) {
    $page = $this->getSession()->getPage();
    $element = $page->find('xpath', '//select//option[contains(text(), "' . $label . '")]');
    if ($element !== NULL) {
      if ($element->isVisible()) {
        return;
      }
      else {
        throw new \InvalidArgumentException("Form item with label \"$label\" not visible.");
      }
    }
    else {
      throw new \InvalidArgumentException("Form item with label \"$label\" not found.");
    }
  }

  /**
   * Checks, that select list element with specified label is not visible on page.
   *
   * @Then I should not see a :label select list form element
   */
  public function iShouldNotSeeASelectListFormElement($label) {
    $page = $this->getSession()->getPage();
    $element = $page->find('xpath', '//select//option[contains(text(), "' . $label . '")]');
    if ($element !== NULL && $element->isVisible()) {
      throw new \InvalidArgumentException("Form item with label \"$label\" is visible.");
    }
  }
}
