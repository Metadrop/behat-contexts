<?php

/**
 * @file
 * Behat Feature Context file.
 */

namespace Metadrop\Behat\Context;

use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Drupal\Core\Url;

/**
 * Class InterfaceTranslationContext.
 *
 * @package Metadrop\Behat\Context
 */
class I18nContext extends RawMinkContext implements Context {

  /**
   * @var \Drupal\DrupalExtension\Context\MinkContext
   */
  protected $minkContext;

  /**
   * Get the necessary contexts.
   *
   * @BeforeScenario
   *
   * @param BeforeScenarioScope $scope
   *   Scope del scenario.
   */
  public function gatherContexts(BeforeScenarioScope $scope) {
    $environment = $scope->getEnvironment();
    $this->minkContext = $environment->getContext('Drupal\DrupalExtension\Context\MinkContext');
  }

  /**
   * Fills in form field with specified label translated
   * Example: When I fill in "username" translated with: "bwayne"
   * Example: And I fill in "bwayne" translated for "username"
   *
   * @When /^(?:|I )fill in "(?P<field>(?:[^"]|\\")*)" translated field with "(?P<value>(?:[^"]|\\")*)"$/
   * @When /^(?:|I )fill in "(?P<field>(?:[^"]|\\")*)" translated field with:$/
   * @When /^(?:|I )fill in "(?P<value>(?:[^"]|\\")*)" for "(?P<field>(?:[^"]|\\")*)" translated field$/
   */
  public function fillFieldTranslated($field, $value)
  {
    $field = $this->getTranslatedText($field);
    $this->minkContext->fillField($field, $value);
  }

  /**
   * Presses button with specified translated title.
   *
   * @When I press the :button translated button
   */
  public function pressTranslatedButton($button) {
    $button = $this->getTranslatedText($button);
    $this->minkContext->pressButton($button);
  }

  /**
   * @Given I press :button translated button in the :region( region)
   */
  public function assertRegionPressButton ($button, $region) {
    $button = $this->getTranslatedText($button);
    $this->minkContext->assertRegionPressButton($button, $region);
  }

  /**
   * @Then I (should )see the translated text :text
   */
  public function assertTranslatedTextVisible($text) {
    $translated_text = $this->getTranslatedText($text);
    $this->minkContext->assertTextVisible($translated_text);
  }

  /**
   * Checks, that current page PATH is equal to specified after going through
   * the translation system for the current language.
   *
   * Example: Then I should be on "/" translated page
   * Example: And I should be on "/bats" translated page
   *
   * @Then /^(?:|I )should be on "(?P<path>[^"]+)" translated page$/
   */
  public function assertTranslatedPageAddress($path)
  {
    $langcode = $this->getCurrentPageLanguage();

    $url = Url::fromUri('internal:' . $path, [
      'language' => \Drupal::languageManager()->getLanguage($langcode),
    ]);
    $path = $url->toString();

    $this->assertSession()->addressEquals($this->locatePath($path));
  }

  /**
   * Get the text translated to the current page language.
   *
   * @param string $text
   *   The untranslated text.
   *
   * @return string
   *   The translated text.
   *
   * @throws \Behat\Mink\Exception\DriverException
   * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
   */
  protected function getTranslatedText(string $text): string {
    $langcode = $this->getCurrentPageLanguage();
    return t($text, [], ['langcode' => $langcode]);
  }

  /**
   * Get the current page langauge based on the lang attribute.
   *
   * @return string
   *   The current page langauge.
   *
   * @throws \Behat\Mink\Exception\DriverException
   * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
   */
  protected function getCurrentPageLanguage(): string {
    return $this->getSession()
      ->getDriver()
      ->getAttribute('//html', 'lang');
  }

}
