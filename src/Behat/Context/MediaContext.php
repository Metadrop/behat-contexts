<?php

namespace Metadrop\Behat\Context;

use Metadrop\Behat\Context\RawDrupalContext;
use Metadrop\Behat\Context\UIContext;
use Metadrop\Behat\Context\WaitingContext;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\MinkExtension\Context\MinkContext;

/**
 * Steps related with media.
 */
class MediaContext extends RawDrupalContext {

  /**
   * Mink context.
   *
   * @var Drupal\DrupalExtension\Context\MinkContext
   */
  protected $minkContext;

  /**
   * UI context.
   *
   * @var Metadrop\Behat\Context\UIContext;
   */
  protected $uiContext;

  /**
   * UI context.
   *
   * @var Metadrop\Behat\Context\WaitingContext;
   */
  protected $waitingContext;

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
    foreach ($environment->getContexts() as $context) {
      if ($context instanceof MinkContext) {
        $this->minkContext = $context;
      }
    }
    $this->uiContext = $environment->getContext(UIContext::class);
    $this->waitingContext = $environment->getContext(WaitingContext::class);
  }

  /**
   * Select existing media.
   */
  public function iSelectMedia($widget, $xpath) {
    if ($widget == 'media_library') {
      $this->getSession()->getPage()->find('xpath', $xpath)->click();
      $this->waitingContext->iWaitForAjaxToFinish(30);
      $this->getSession()->getPage()->find('css', '.ui-dialog-buttonpane.ui-widget-content .media-library-select.form-submit')->click();
      $this->waitingContext->iWaitForAjaxToFinish(30);
    }
    else {
      throw new \InvalidArgumentException('Only "media_library" widget is supported');
    }
  }

  /**
   * Upload a media to media field.
   *
   * @Then I upload media with name :media_title to :field field using :widget widget
   * @Then I assign the media with name :media_title to :field field
   */
  public function iUploadMediaWithNameToField($media_title, $field, $widget = 'media_library') {
    if ($widget == 'media_library') {
      $this->uiContext->iClickOnTheElementWithXpath("//input[contains(@id, 'edit-" . $field . "-open-button')]");
      $this->waitingContext->iWaitForAjaxToFinish(30);
      $xpath = "//div[contains(@class, 'views-field-media-library-select-form')]//div[contains(@class, 'form-type--checkbox')]/label[contains(text(),'". $media_title ."')]/following-sibling::input";
      $this->iSelectMedia($widget, $xpath);
    }
  }

  /**
   * Check media is visible.
   *
   * @Then I should see the :media_type media with name :media_title
   */
  public function iShouldSeeTheMedia($media_type, $media_title) {
    switch ($media_type) {
      case "image":
        $xpath = "//img[contains(@src, '$media_title')]";
        $image = $this->getSession()->getPage()->find('xpath', $xpath);
        if (empty($image)) {
          throw new \Exception("The image '$media_title' was not found");
        }
        break;

      case "document":
        $xpath = "//a[contains(@href, '$media_title')]";
        $document = $this->getSession()->getPage()->find('xpath', $xpath);
        if (empty($document)) {
          throw new \Exception("The document '$media_title' was not found");
        }
        break;

    }
  }

}
