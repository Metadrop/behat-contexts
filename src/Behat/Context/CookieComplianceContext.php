<?php

namespace Metadrop\Behat\Context;

use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Driver\Selenium2Driver;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Allows checking that the sites are cookie gdpr compliant.
 *
 * It checks that before accepting cookies there are not cookies
 * saved in the browser, and when the cookies are accepted, the
 * expected cookies appears.
 */
class CookieComplianceContext extends RawMinkContext {

  /**
   * List of cookies that may load cookies.
   */
  const THIRD_PARTY_COOKIE_HOSTS = [
    'addthis.com',
    'addtoany.com',
    'adsrvr.org',
    'amazon-adsystem.com',
    'bing.com',
    'bounceexchange.com',
    'bouncex.net',
    'criteo.com',
    'criteo.net',
    'dailymotion.com',
    'doubleclick.net',
    'everettech.net',
    'facebook.com',
    'facebook.net',
    'googleadservices.com',
    'googlesyndication.com',
    'krxd.net',
    'liadm.com',
    'linkedin.com',
    'outbrain.com',
    'rubiconproject.com',
    'sharethis.com',
    'Taboola.com',
    'twitter.com',
    'vimeo.com',
    'yahoo.com',
    'youtube.com',
  ];

  /**
   * Selector that accepts default cookie categories.
   *
   * @var string
   */
  protected string $cookieAgreeSelector;

  /**
   * Cookie banner selector.
   *
   * @var string
   */
  protected string $cookieBannerSelector;

  /**
   * List of cookies classified with category.
   *
   * @var array
   */
  protected array $cookies;

  /**
   * List of cookies to ignore.
   *
   * @var array
   */
  protected array $cookiesIgnored;

  /**
   * List of third party domains that loads cookies to ignore.
   *
   * @var array
   */
  protected array $cookiesThirdPartyDomainsIgnored;

  /**
   * List of third party domains that loads cookies to add.
   *
   * Used to complement THIRD_PARTY_COOKIE_HOSTS list.
   *
   * @var array
   */
  protected array $cookiesThirdPartyDomainsIncluded;

  /**
   * Cookie compliance constructor.
   *
   * @param string $cookie_agree_selector
   *   Selector that accepts default cookie categories.
   * @param string $cookie_banner_selector
   *   Cookie banner selector.
   * @param array $cookies
   *   List of cookies classified with category.
   * @param array $cookies_ignored
   *   List of cookies to ignore.
   * @param array $cookies_third_party_domains_ignored
   *   List of third party domains that loads cookies to ignore.
   * @param array $cookies_third_party_domains_included
   *   List of third party domains that loads cookies to add.
   */
  public function __construct(
    string $cookie_agree_selector,
    string $cookie_banner_selector,
    array $cookies,
    array $cookies_ignored = [],
    array $cookies_third_party_domains_ignored = [],
    array $cookies_third_party_domains_included = []
  ) {
    $this->cookieAgreeSelector = $cookie_agree_selector;
    $this->cookieBannerSelector = $cookie_banner_selector;
    $this->cookies = $cookies;
    $this->cookiesIgnored = $cookies_ignored;
    $this->cookiesThirdPartyDomainsIgnored = $cookies_third_party_domains_ignored;
    $this->cookiesThirdPartyDomainsIncluded = $cookies_third_party_domains_included;
  }

  /**
   * Accept cookie compliance.
   *
   * It accepts it by accepting all the categories
   * that are accepted by default.
   *
   * @Then I accept cookies
   */
  public function iAcceptCookies() {
    $agree_button = $this->getSession()->getPage()->find('css', $this->cookieAgreeSelector);
    if ($agree_button instanceof NodeElement) {
      // Some cookie banners have animations that do not let click the agree
      // button after the animation ends. That's why we wait one second.
      if (!$agree_button->isVisible()) {
        sleep(1);
      }
      $agree_button->press();
      if (!$this->getSession()->wait(
        10000,
        sprintf('document.querySelector("%s") == null || document.querySelector("%s").style.visibility == "hidden"',
          $this->cookieBannerSelector, $this->cookieBannerSelector))) {
        throw new \Exception(sprintf('The cookie banner with selector "%s" is stil present after accepting cookies.', $this->cookieBannerSelector));
      }
    }
    else {
      throw new \Exception('The agree button do not appears.');
    }
  }

  /**
   * Check the main cookies of a specific type are not present.
   *
   * @Then the cookies of :type type have not been loaded
   */
  public function cookiesShouldBeEmpty(string $type) {
    $cookies_list = $this->cookies[$type] ?? [];
    foreach ($cookies_list as $cookie_name) {
      $cookie_value = $this->getSession()->getDriver()->getCookie($cookie_name);
      if (!empty($cookie_value)) {
        throw new \Exception(sprintf("Cookie with name %s is saved, but it shouldn\'t.", $cookie_name));
      }
    }
  }

  /**
   * Check the main cookies of a specific type are present.
   *
   * @Then the cookies of :type type have been loaded
   */
  public function cookiesHaveBeenSaved(string $type) {
    $cookies_list = $this->cookies[$type] ?? [];
    foreach ($cookies_list as $cookie_name) {
      $cookie_value = $this->getSession()->getDriver()->getCookie($cookie_name);
      if (empty($cookie_value)) {
        throw new \Exception(sprintf('Cookie with name %s is not saved, but it should.', $cookie_name));
      }
    }
  }

  /**
   * Wait until the cookie banner appears.
   *
   * @When I wait cookie banner appears
   */
  public function iWaitCookieBannerAppears() {
    if (!$this->getSession()->wait(10000, sprintf('document.querySelector("%s") != null', $this->cookieBannerSelector))) {
      throw new \Exception(sprintf('The cookie banner with selector "%s" does not appear.', $this->cookieBannerSelector));
    }
  }

  /**
   * Check there aren't cookies.
   *
   * Only compatible with Selenium2 Driver.
   *
   * @Then there should not be any cookies loaded
   */
  public function thereShouldNotBeAnyCookie() {
    if (!$this->getSession()->getDriver() instanceof Selenium2Driver) {
      throw new \Exception('This step is only supported by Selenium2 Driver');
    }

    $errors = [];
    try {
      $this->analyzeHostCookies();
    }
    catch (\Exception $exception) {
      $errors[] = sprintf('  - %s', $exception->getMessage());
    }

    try {
      $this->analyzeThirdPartyCookies();
    }
    catch (\Exception $exception) {
      $errors[] = sprintf('  - %s', $exception->getMessage());
    }

    if (!empty($errors)) {
      throw new \Exception(sprintf("Errors found:\n%s", implode("\n", $errors)));
    }

  }

  /**
   * Analyze there aren't cookies at the host.
   *
   * If there are, it will throw an exception
   * and show a table with all the loaded cookies.
   */
  protected function analyzeHostCookies() {
    $webdriver_session = $this->getSession()->getDriver()->getWebDriverSession();
    $cookies = array_filter($webdriver_session->getAllCookies(), function ($cookie) {
      return !in_array($cookie['name'], $this->cookiesIgnored);
    });

    $cookies_ignored_loaded = array_intersect($this->cookiesIgnored, array_column($cookies, 'name'));
    if (!empty($cookies_ignored_loaded)) {
      print sprintf('The following cookies are loaded but they are setup as ignored: %s', implode(',', $cookies_ignored_loaded));
    }

    if (!empty($cookies)) {
      $this->showCookies($cookies);
      throw new \Exception('There are cookies but there should not be any cookie.');
    }
  }

  /**
   * Shows the list of cookies that have been found.
   *
   * Array of cookies.
   *
   * @param array $cookies
   *   List of cookies to print.
   */
  protected function showCookies(array $cookies) {
    $output = new BufferedOutput();
    $cookiesTable = new Table($output);

    $cookiesTable->setHeaders(['Domain', 'Name', 'Value']);

    $cookiesTable->setRows(array_map(function ($cookie) {
      return [
        $cookie['domain'],
        $cookie['name'],
        $cookie['value'],
      ];
    }, $cookies));

    $output->writeln("\nCookies found:\n");

    $cookiesTable->render();

    echo $output->fetch();
  }

  /**
   * Analyze there aren't third party cookie iframes at the host.
   *
   * If there are, it will throw an exception and show a table with the
   * list of iframes that are loading cookies.
   */
  protected function analyzeThirdPartyCookies() {
    $page_html_document = new \DOMDocument('4.0');

    $page_html_document->loadHTML($this->getSession()->getPage()->getOuterHtml(), LIBXML_NOERROR);
    $page_html_xpath = new \DOMXPath($page_html_document);

    $third_party_cookie_urls_loaded = [];

    $potential_cookie_source_domains = array_unique(array_merge(static::THIRD_PARTY_COOKIE_HOSTS, $this->cookiesThirdPartyDomainsIncluded));
    $potential_cookie_source_domains = array_diff($potential_cookie_source_domains, $this->cookiesThirdPartyDomainsIgnored);

    /** @var \DOMElement $iframe */
    foreach ($page_html_xpath->query('//iframe') as $iframe) {
      $iframe_src = $iframe->getAttribute('src');
      if (!empty($iframe_src)) {
        $iframe_host = parse_url($iframe_src, PHP_URL_HOST);
        foreach ($potential_cookie_source_domains as $third_party_cookie_host) {
          if (!in_array($third_party_cookie_host, $this->cookiesThirdPartyDomainsIgnored) && str_ends_with($iframe_host, $third_party_cookie_host)) {
            $third_party_cookie_urls_loaded[] = [
              'url' => $iframe_src,
              'domain' => $third_party_cookie_host,
            ];
          }
        }
      }
    }

    if (!empty($third_party_cookie_urls_loaded)) {
      $this->showThirdPartyCookieUrls($third_party_cookie_urls_loaded);
      throw new \Exception(sprintf('There are iframes that are loading not compliant third party cookies.'));
    }
  }

  /**
   * Show the URLs that are loading third party cookies.
   *
   * @param array $urls
   *   URL list.
   */
  protected function showThirdPartyCookieUrls(array $urls) {
    $output = new BufferedOutput();
    $urlsTable = new Table($output);

    $urlsTable->setHeaders(['Domain', 'Url']);

    $urlsTable->setRows(array_map(function ($url) {

      if (strlen($url['url']) > 128) {
        $url['url'] = sprintf('%s...', substr($url['url'], 0, 128));
      }
      return [
        $url['domain'],
        $url['url'],
      ];
    }, $urls));

    $output->writeln("\nPotential cookies source found (iframe):\n");

    $urlsTable->render();

    $output->writeln("\nPlease check and exclude them if not applicable. Set the cookies_third_party_domains_ignored variable up.\n");

    echo $output->fetch();
  }

  /**
   * Check cookie exists.
   *
   * @Given the cookie with name :cookie_name exists
   */
  public function cookieExists($cookie_name) {
    $cookie_value = $this->getSession()->getDriver()->getCookie($cookie_name);
    if (empty($cookie_value)) {
      throw new \Exception(sprintf("Cookie with name %s does not have value.", $cookie_name));
    }
  }

  /**
   * Check cookie exists with value.
   *
   * @Given the cookie with name :cookie_name exists with value :value
   */
  public function cookieExistsWithValue($cookie_name, $value) {
    $this->cookieExists($cookie_name);
    $cookie_value = $this->getSession()->getDriver()->getCookie($cookie_name);
    if ($cookie_value != $value) {
      throw new \Exception(sprintf("Cookie with name %s does not have the expected value %s, it has %s.", $cookie_name, $value, $cookie_value));
    }
  }

}
