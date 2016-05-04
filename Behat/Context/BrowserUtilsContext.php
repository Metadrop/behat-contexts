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
   * @Then I check element :name has the :elect selected
   * @TODO la variable $label no pinta el texto al fallar el step
   */
  public function elementHasTheSelected($name ,$elect) {
    $session = $this->getSession();
    $label = $session->getPage()->find('xpath', '//*/div[contains(@class,"form-type-select")]/label[contains(text(), "' . $name . '")]');
    $opt = $session->getPage()->find('xpath', '//*/select/option[contains(@selected, "selected")][contains(text(), "' . $elect . '")]');
    if (NULL === $label) {
      throw new \InvalidArgumentException('name: ' . $name . ' != a label: ' . $label . "" );
    }
    elseif (NULL === $opt) {
      throw new \InvalidArgumentException('elect ' . $elect . ' != a opt: ' . $opt . "");
    }
  }

  /**
   * @Then form :arg1 element :arg2 is required
   */
  public function formElementIsRequired($type, $label) {
    $page = $this->getSession()->getPage();
    $xpath = "//label[contains(*, '{$label}')]/../{$type}[contains(@class, 'required')]";
    $element = $page->find('xpath', $xpath);
    if (null === $element) {
      throw new \InvalidArgumentException(sprintf('Could not evaluate XPath: "%s"', $xpath));
    }
  }

  /**
   * @Then I check element :arg1 is disabled
   */
  public function elementIsDisabled($name) {
    $session = $this->getSession();
    $element_disabled = $session->getPage()->find('xpath', '//*[contains(@value, "' . $name . '")][contains(@disabled, "disabled")]');
    $element = $session->getPage()->find('xpath', '//*[contains(@value, "' . $name . '")]');
    if ($element !== NULL && $element_disabled === NULL) {
      throw new Exception('Element "' . $name . '" is not disabled.');
    }
    elseif ($element === NULL) {
      throw new Exception('Element not exists.');
    }
  }

  /**
   * Click on the element with the provided xpath query
   *
   * @When /^I click on the element with xpath "([^"]*)"$/
   */
  public function iClickOnTheElementWithXPath($xpath) {
    $session = $this->getSession(); // get the mink session
    $element = $session->getPage()->find('xpath', $session->getSelectorsHandler()->selectorToXpath('xpath', $xpath)); // runs the actual query and returns the element
    // errors must not pass silently
    if (null === $element) {
      throw new \InvalidArgumentException(sprintf('Could not evaluate XPath: "%s"', $xpath));
    }

    // ok, let's click on it
    $element->click();
  }

  /**
   * @Given /^I select the radio button with name "([^"]*)"$/
   */
  public function iSelectTheRadioButtonWithName($name) {
    $session = $this->getSession();
    $element = $session->getPage()->find('xpath', "//label[contains(*, '{$name}')]/../input");
    // errors must not pass silently
    if (NULL === $element) {
      throw new \InvalidArgumentException(sprintf('No se ha encontrado el name ' . $name));
    }
    // ok, let's click on it
    $element->click();
  }

  /**
   * Checks, that form element with specified label is visible on page.
   *
   * @Then I should see a :label textfield form element
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
        throw new \InvalidArgumentException("Form item with label \"$label\" not visible.");
      }
    }
    else {
      throw new \InvalidArgumentException("Form item with label \"$label\" not found.");
    }
  }

  /**
   * Checks, that form element with specified label is visible on page.
   *
   * @Then I should not see a :label textfield form element
   */
  public function iShouldNotSeeATextfieldFormElement($label) {
    $page = $this->getSession()->getPage();
    $xpath = "//label[contains(*, '{$label}') or contains(text(), '{$label}')]/../input";
    $element = $page->find('xpath', $xpath);
    if ($element !== NULL && $element->isVisible()) {
      throw new \InvalidArgumentException("Form item with label \"$label\" is visible.");
    }
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
