<?php

use Metadrop\Behat\Context\RawDrupalContext;
use Metadrop\Behat\Context\UIContext;
use Metadrop\Behat\Context\WaitingContext;
use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;

/**
 * Steps related with media.
 */
class MediaContext extends RawDrupalContext {

  protected $medias = [];

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
    $this->minkContext = $environment->getContext('Drupal\DrupalExtension\Context\MinkContext');
    $this->uiContext = $environment->getContext(UIContext::class);
    $this->waitingContext = $environment->getContext(WaitingContext::class);
    $this->mediaContext = $environment->getContext(MediaContext::class);
  }

  /**
   * Delete created medias.
   *
   * @AfterScenario
   */
  public function deleteMedias() {
    foreach ($this->medias as $media) {
      $this->getCore()->entityDelete('media', $media);
    }
  }

  /**
   * Select existing media.
   *
   */
  public function iSelectMedia($widget, $xpath) {
    if ($widget == 'media_library') {
      $this->getSession()->getPage()->find('xpath', $xpath)->click();
      $this->getSession()->getPage()->find('css', '.ui-dialog-buttonpane.ui-widget-content .media-library-select.form-submit')->click();
    }
  }

  /**
   * Upload a media to media field.
   *
   * @Then I upload media with name :media_title to :field field using :widget widget
   */
  public function iUploadMediaWithNameToField($media_title, $field, $widget) {
    if ($widget == 'media_library') {
      $this->uiContext->iClickOnTheElementWithXpath("//input[contains(@id, 'edit-" . $field . "-open-button')]");
      $this->waitingContext->iWaitForAjaxToFinish(30);
      $xpath = "//*[@id='drupal-modal']//label[text()='Select " . $media_title . "']/following-sibling::input";
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
