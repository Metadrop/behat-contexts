# Behat Contexts for Drupal


Contexts that we use with Behat 3.x tests on Drupal sites.

This repository is based on [Nuvole drupal extension](https://github.com/nuvoleweb/drupal-behat).

## Install

Install with [Composer](http://getcomposer.org):

    composer require metadrop/behat-contexts

## Configure

Each context may have its own configuration. [Here is an example](https://github.com/Metadrop/behat-contexts/blob/dev/behat.yml.dist) with all the contexts added.

## Contexts

### Cache context

Steps to clear caches.

#### Steps

- Given :path page cache is flushed
  Clear specific page caches.

- Given :view view data cache is flushed
  Clear caches for a specific view. Only available for Drupal 7, pending to implement in D8.

### Content authored context

Allow created content owned by logged in user.

#### Steps

 - Given own :type content:
   Create content with the author as the current user.

### Cron context

Helpers to execute cron.

#### Steps

 - Given I run elysia cron
   Run elysia cron. Only for D7.

 - Given I run the elysia cron :job job
   Run elysia-cron-job. Only for D7.

 - Given I run the cron of Search API
   Run search api cron. Only for D7.

 - Given I run the cron of Search API Solr
   Run search api solr cron. Only for D7.

### Cookie compliance context

Allows checking that the sites are cookie GDPR compliant.
This context works with any cookie banner integration : onetrust, cookies, eu cookie compliance...

It checks that before accepting cookies there are not cookies
saved in the browser, and when the cookies are accepted, the
expected cookies appears.

The context parameters are:

- **cookie_manager_type**: Types of cookie managers predefined. It also allows you to overwrite the other parameters if you consider it necessary. *Cookie managers implemented*:
    - onetrust
    - eu_cookie_compliance
- **cookie_agree_selector**: The CSS selector of the button to accept the default cookies.
- **cookie_reject_selector**: The CSS selector of the button to reject all cookie categories.
- **cookie_banner_selector**: The CSS selector of the cookie compliance banner.
- **cookies**: Map of cookies that should be handled by each cookie category. The key
  is the cookie category and the value is the list of cookies that will be present after
  accepting the cookie compliance category.
- **cookies_ignored**: List of cookies that must be ignored if they appear at the step 'There are
  no cookies loaded'. Add here cookies when they can't be managed at the server side.
- **cookies_third_party_domains_ignored**: List of domains reported that contains potential cookies loaded
  but they can be ignored because no cookies are being loaded.
- **cookies_third_party_domains_included**: List of domains that are not present in the default list of domains
  checked by the context, and is needed to be checked those sites are not loading cookies by iframes.

Example configuration *without* Cookie Manager type:

```yaml
  - CookieComplianceContext:
      cookie_agree_selector: 'button.agree-button-example'
      cookie_reject_selector: 'button.reject-button-example'
      cookie_banner_selector: '.cookie-compliance-banner-example'
      cookies:
        mandatory:
          - 'cookie-mandatory'
          - 'cookie-mandatory-categories'
        analytics:
          - '_ga'
      # Optional configuration:
      cookies_ignored:
        - cookieA
        - cookieB
      cookies_third_party_domains_ignored:
        - example.com
      cookies_third_party_domains_included:
        - extra-analytics-service.com
```

Example configuration *with Cookie Manager type*:

```yaml
  - CookieComplianceContext:
      cookie_manager_type: onetrust
      ...
      ...
```

#### Steps

- **Then I accept cookies**: Accept cookies by clicking the accept button in cookie popup or banner.

- **Then I reject cookies**: Reject cookies by clicking the reject button in cookie popup or banner.

- **Then the cookies of :type type have not been loaded**: Assert the cookies of a specific category are not present.

- **Then the cookies of :type type have been loaded**:  Assert the cookies of a specific category are present.

- **When I wait cookie banner appears**:  Wait until the cookie banner is loaded.

- **Then there should not be any cookies loaded**:  Check there are no cookies loaded at all. It also reports
  potential cookie source coming from third party iframes (s.e.: youtube, doubleclick, etc).

- **Given the cookie with name :cookie_name exists**:  Check if the cookie exists.

- **Given the cookie with name :cookie_name exists with value :value**: Check that the cookie exists with the specific value.

  By default, only those iframes which domains belongs to the **THIRD_PARTY_COOKIE_HOSTS** CookieComplianceContext constant will be detected as iframes that will add unwanted cookies.

#### Tags

- **@cookies-accepted**: Accept cookies automatically adding the tag to the test.

- **@cookies-rejected**: Reject cookies automatically adding the tag to the test.


### DebugContext

Simple context to help debugging tests with some steps. Additionally, it hooks in the after step event to add a step that generates an error report on failed steps.

This report includes:
  - A file with the HTML page content.
  - A file with the current URL and the error exception dump.
  - If available, a file with current page state.

#### Steps

- Then capture full page with a width of :width

  Saves a screenshot of current page with the given width to a file.

- Then capture full page with a width of :width with name :filename in configured directory (screenshots_path).

  Saves a screenshot of current page with the given width to a given filename in configured directory (screenshots_path).

- Then capture full page with width of :width to :path

  Saves a screenshot of current page with the given width to a file in the given path. If path is relative screenshots_path config value is used as root.

- Then capture full page with width of :width to :path with name :filename

  Saves a screenshot of current page with the given width to a file in the given path to a given filename. If path is relative screenshots_path config value is used as root.

- Then save last response

  Saves page content to a file.

- Then save last response to :path

  Saves page content to a file in the given path.

- Then save last response to :path

  Halts test for a given amount of seconds. Useful when debugging tests with timing issues. Don't use this step in real tests.


#### Configuration
  Add DebugContext to your suite.

  This is an example when bootstrap directorty is in DRUPALROOT/sites/all/tests/behat/bootstrap.

```
default:
  autoload:
    ...
  suites:
    default:
      ...
      contexts:
        - Metadrop\Behat\Context\DebugContext:
            parameters:
              'report_on_error': true
              'error_reporting_url': 'https://example.com/sites/default/files/behat/errors'
              'error_reporting_path': '/var/www/html/docroot/sites/default/files/behat/errors'
              'screenshots_path': '/var/www/html/docroot/sites/default/files/behat/screenshots'
              'page_contents_path': '/var/www/html/docroot/sites/default/files/behat/pages'
        - Metadrop\Behat\Context\EntityContext:
            parameters:
              'purge_entities':
                - user
                - custom_entity
```

**Parameters**
  - report_on_error: If _true_ error reports are generated on failed steps.
  - error_reporting_path: Path where reports are saved.
  - error_reporting_url: Url where the error screenshots will be shown. As we can see in example, the url must point to the directory where we save the reports, and the directory must be accesible through website.
  - screenshots_path: Path where screenshots are saved. Report screenshots are saved in the report path, here only screenshots from _capture full page_ steps are saved.
  - page_contents_path: Path where page contents are saved. Report page contents are saved in the report path, here only page contents from _save page content_ steps are saved.

### Entity Context

Agnostic steps related with entities.

#### Steps
 - Given I go to the last entity :entity created
   Go to last entity created.

 - Given I go to the last entity :entity with :bundle bundle created
   Go to the last entity created from a specific bundle.

 - Given I go to :subpath of the last entity :entity created
   Go to last entity created subpath (s.e.:node/1/edit).

 - Given I go to :subpath of the last entity :entity with :bundle bundle created.
   Go to last entity created subpath (s.e.:node/1/edit) from a specific bundle.

### File context

Create files in drupal.

#### Steps

   - Given file with name :filename
     Create file in drupal file system. Files are extracted from files_path set in behat.

   - Given file with name :filename in the :directory directory
     Create file in drupal file system in a specific directory. Directory must start with file system (public:// , private://). Default is public:// .

### Form Context

Steps for form elements.

#### Steps

   - Then form :type element :label should be required
     Check a form element of a specific type (s.e.: input, select) with label is required.

   - Then form :type element :label should not be required
     Check a form element of a specific type (s.e.: input, select) with label isn't required.

### Node Access context

Steps related with node access system. Only for D7.

#### Steps

   - Given the access of last node created is refreshed
     Refresh node grants from the last node.

   - @Given the access of last node created with :bundle bundle is refreshed
     Refresh node grants from the last node of a specific content type.

### Paragraphs context

Steps to attach paragraphs to content.

#### Steps

   - Given paragraph of :paragraph_type type referenced on the :field_paragraph field of the last content:
     Create a paragraph with fields and attach it to the last node created.

### Url Context

Steps to check url values

#### Steps

   - Then current url should have the ":param" param with ":value" value
     Check an url has a specific value in a query parameter.

   - Then current url should not have the ":param" param with ":value" value
     Check an url hasn't a specific value in a query parameter.


### UIContext

This context provides steps for certain UI elements.

#### Steps

- Given I select :option from :select chosen.js select box

  Selects and option from a Chosen select widget. Only for sinlge selection, it
  doesn't work with multiple selection enabled or tag style.

  See https://harvesthq.github.io/chosen/

#### Example of how to use internal method elementShouldBeInPosition

    /**
    * Example of implementation elementShouldBeInPosition on a custom step.
    *
    * @Then the card on the infinite scroll view with title :title should be in position :position.
    */
    public function theCardWithTitleShouldBeInPositionExample(string $title, string $position) {
      $this->elementShouldBeInPosition('item-list-css-selector', $title, 'views-infinite-scroll-content-wrapper', $position);
    }

### WaitingContext

This context provides waiting time after defined steps, and extra waiting steps.

Waiting steps - Sometimes steps are running faster than our site, if this is the case you can delay them a few seconds. Don't abuse of this functionality, if Behat is running slow maybe there is a performance global site issue that needs to be solved first!

#### Steps

    - Then I wait for :seconds second(s)
      Step wait a defined seconds before execute next step.


#### Configure some waiting time before execute next step

Set on `behat.yml` step action with wait time in seconds before execute next step.

##### Configuration
```
default:
  autoload:
    ...
  suites:
    default:
      ...
      contexts:
        - Metadrop\Behat\Context\WaitingContext:
            parameters:
              waiting_steps:
                'I go to': 1
                'I click': 1
                'I scroll': 1
                'I press': 2
```

##### Action

Wait 1 second before the next step to `Then I press "Log in"` (`Then the url should match "/example-page"`)

```
Then I press "Log in"
Then the url should match "/example-page"
```

#### Configuration

No configuration needed.

### Video Recording Context

This context helps with video recording of Behat scenarios by displaying scenario metadata in the browser before each test. It is useful for identifying tests in video recordings.

#### Functionality

- Optionally displays a green screen for a configurable duration before each scenario, to help segment videos.
- Optionally displays an info screen with the feature description, scenario name, background steps, and scenario steps, for a configurable duration.
- All options are configurable via context parameters.

#### Configuration

Add `VideoRecordingContext` to your suite in `behat.yml`:

```yaml
- Metadrop\Behat\Context\VideoRecordingContext:
    parameters:
        - Metadrop\Behat\Context\VideoRecordingContext:
            parameters:
              enabled: true
              show_test_info_screen: true
              show_test_info_screen_time: 2000
              show_green_screen: false
              show_green_screen_time: 1000
              show_step_info_bubble: true
              show_step_info_bubble_time: 2000
              show_error_info_bubble: true
              show_error_info_bubble_time: 2000
