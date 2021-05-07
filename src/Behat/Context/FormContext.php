<?php

namespace Metadrop\Behat\Context;

use NuvoleWeb\Drupal\DrupalExtension\Context\RawMinkContext;
use Behat\Mink\Element\NodeElement;

class FormContext extends RawMinkContext {

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

    // Check required class or attribute required.
    $xpath_required = "(//label[contains(text(), '{$label}')]/..//{$type}[contains(@required, 'required')] | //label[contains(text(), '{$label}')]/..//{$type}[contains(@class, 'required')])";
    $element_required = $page->find('xpath', $xpath_required);

    return NULL !== $element_required;
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
   * @Given I fill :label start date with :date
   */
  public function fillDateStartFieldDate($label, $date) {
    $this->fillDateField($label, 'start', 'date', $date);
  }

  /**
   * @Given I fill :label start date hour with :date
   */
  public function fillDateStartFieldHour($label, $date) {
    $this->fillDateField($label, 'start', 'time', $date);
  }

  /**
   * @Given I fill :label end date with :date
   */
  public function fillDateEndFieldDate($label, $date) {
    $this->fillDateField($label, 'end', 'date', $date);
  }

  /**
   * @Given I fill :label end date hour with :date
   */
  public function fillDateEndFieldHour($label, $date) {
    $this->fillDateField($label, 'end', 'time', $date);
  }

  /**
   * Fill a date field.
   *
   * It is only tested with date fields with start and end date.
   *
   * @param string $label
   *   Input's label.
   * @param string $part
   *   Part of the date (start / end).
   * @param string $field
   *   Part of the date field (date / hour).
   * @param string $date
   *   Accepted formats are 'Y-m-d' for date and 'h:i:s' for hours.
   *
   * @throws \Exception
   *   When the field does not exists.
   */
  protected function fillDateField($label, $part, $field, $date) {
    switch ($part) {
      case 'end':
        $date_part_field = 'end_value';
        break;

      case 'start':
      default:
        $date_part_field = 'value';
    }

    $xpath = sprintf(
      '//span[contains(text(), "%s")]/../../div//input[contains(@name, "[%s][%s]")]',
      $label,
      $date_part_field,
      $field,
    );

    $page = $this->getSession()->getPage();
    $element = $page->find('xpath', $xpath);
    if ($element instanceof NodeElement) {
      $element->setValue($date);
    }
    else {
      throw new \Exception(sprintf('Date field with label "%s" %s value not found', $label, $part));
    }
  }

}
