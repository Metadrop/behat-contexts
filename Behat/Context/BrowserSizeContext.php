<?php

/**
 * @file
 *
 * BrowserSizeContext Context for Behat.
 *
 */

namespace Metadrop\Behat\Context;

use Behat\Behat\Context\SnippetAcceptingContext;
use Drupal\DrupalExtension\Context\RawDrupalContext;

class BrowserSizeContext extends RawDrupalContext implements SnippetAcceptingContext {

  /**
   * Context parameters.
   *
   * @var array
   */
  protected $customParams;

  /**
   * Constructor.
   *
   * Save class params, if any.
   *
   * @param array $parameters
   */
  public function __construct($parameters) {

    // Default values.
    $defaults = array(
      'Default'  => array('width' => 1200, 'height' => 800),
      'Full'     => array('width' => 1200, 'height' => 800),
      'Tablet H' => array('width' => 1024, 'height' => 768),
      'Tablet V' => array('width' => 800, 'height' => 1024),
      'Mobile H' => array('width' => 650, 'height' => 370),
      'Mobile V' => array('width' => 350, 'height' => 650),
    );

    // Collect received parameters.
    $this->customParameters = array();
    if (!empty($parameters)) {
      // Filter any invalid parameters.
      $this->customParameters = array_intersect_key($parameters, $defaults);
    }

    // Apply default values.
    $this->customParameters += $defaults;
  }

  /**
   * Step to resize the window by default before scenario.
   * This step is executed only if the stage has the tag @javascript.
   * @BeforeScenario @javascript
   */
  public function browserWindowSizeIsDefault() {
   $this->getSession()->resizeWindow($this->customParameters['Default']['width'], $this->customParameters['Default']['height'], 'current');
   print_r("Browser Window Size: " . $this->customParameters['Default']['width'] . 'x' . $this->customParameters['Default']['height'] . ' px');
  }

  /**
   * Step to resize the window with a given variable
   * @See $defaults
   * This step is executed only if the stage has the tag @javascript.
   * @Given Browser window size is :arg1
   */
  public function browserWindowSizeIs($name) {
      if (array_key_exists($name, $this->customParameters)) {
        $size = $this->customParameters[$name];
        $this->getSession()->resizeWindow($size['width'], $size['height'], 'current');
        print_r("Browser Window Size: " . $size['width'] . "x" . $size['height'] . " px");
      }
      else {
        throw new Exception('Need select one of: ' . "\n" . implode("\n" , array_keys($this->customParameters)));
    }
  }
}
