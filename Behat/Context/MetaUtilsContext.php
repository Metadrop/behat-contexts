<?php

/**
 * @file
 *
 * MetaUtilsContext Context for Behat.
 *
 */

namespace Metadrop\Behat\Context;

use Behat\Behat\Context\SnippetAcceptingContext;
use Drupal\DrupalExtension\Context\RawDrupalContext;

class MetaUtilsContext extends RawDrupalContext implements SnippetAcceptingContext {

  /**
   * @Given /^I Check display the metatags with title "([^"]*)" and description "([^"]*)"$/
   */
  public function iCheckDisplayTheMetatagsWithTitle($title, $description) {
    // Comprobar los datos
    // Por el momento usamos para el test la firma de (No más muertes en el Mediterráneo), tildes

    $temp = $this->getElementByCss("meta[property='og:title'][content=" . $title . "]");
    $temp2 = $this->getElementByCss("meta[name='twitter:description'][content=" . $description . "]");
    $temp3 = $this->getElementByCss("meta[name='twitter:title'][content='behat petition']");

    if (is_null($temp)) {
      throw new \Exception('metatags "og:title" not found');
    }
    elseif (is_null($temp2)) {
      throw new \Exception('metatags "twitter:description" not found');
    }
    elseif (is_null($temp3)) {
      throw new \Exception('metatags "twitter:title" not found');
    }
  }

  /**
   * @Given twitter metadata Title is :arg1 and descripiton is :arg2
   */
  function twitterMetadataTitleIsDescriptionIs($title, $description) {
    $this->metadataCheck("name='twitter:title'", $title);
    $this->metadataCheck("name='twitter:description'", $description);
    var_dump($this->metadataCheck());
    if (is_null($this->metadataCheck())){
      throw new \Exception('Error twitter');
    }
  }
  /**
   * @Given og metadata Title is :arg1 and descripiton is :arg2
   */
  function ogMetadataTitleIsDescripcionIs($title, $description) {

    $this->metadataCheck("property='og:title'", $title);
    $this->metadataCheck("property='og:description'", $description);
    var_dump($this->metadataCheck());
    if (is_null($this->metadataCheck())) {
      throw new \Exception('Error twitter');
    }

  }

  function metadataCheck($locator, $expected_value) {
    $this->getElementByCss("meta[" . $locator . "][content='" . $expected_value . "'");
    $expected_value = $this->getElementByCss();
  }
}
