# Behat Contexts for Drupal

Contexts that we use with Behat 3.x tests on Drupal sites.

This repository is based on [Nuvole Drupal extension](https://github.com/nuvoleweb/drupal-behat).

## Table of Contents

- [Install](#install)
- [Configure](#configure)
- [Testing](#testing)
- [Contexts](#contexts)
  - [AntiSpam Context](#antispam-context)
  - [Cache context](#cache-context)
  - [Content authored context](#content-authored-context)
  - [Cron context](#cron-context)
  - [Cookie compliance context](#cookie-compliance-context)
  - [DebugContext](#debugcontext)
  - [Drupal Groups Extended Context](#drupal-groups-extended-context)
  - [Drupal Organic Groups Extended Context](#drupal-organic-groups-extended-context)
  - [Entity Context](#entity-context)
  - [File context](#file-context)
  - [Form Context](#form-context)
  - [I18n Context](#i18n-context)
  - [Logs Context](#logs-context)
  - [Media Context](#media-context)
  - [Node Access context](#node-access-context)
  - [Paragraphs context](#paragraphs-context)
  - [Search API Context](#search-api-context)
  - [Url Context](#url-context)
  - [UIContext](#uicontext)
  - [Users Context](#users-context)
  - [Users Random Context](#users-random-context)
  - [WaitingContext](#waitingcontext)
  - [Video Recording Context](#video-recording-context)

## Install

Install with [Composer](http://getcomposer.org):

    composer require metadrop/behat-contexts

## Configure

Each context may have its own configuration. [Here is an example](https://github.com/Metadrop/behat-contexts/blob/dev/behat.yml.dist) with all the contexts added.

## Testing

This library includes a BATS-based testing infrastructure to validate Behat context behaviors and outputs.

### Running Tests Locally

```bash
# Set required environment variables
export BEHAT_CONTEXTS_SOURCE_PATH=$(pwd)
export GITHUB_TOKEN=your_github_token

# Run tests
./tests/run_tests_locally.sh
```

### Running Specific Tests

```bash
bats tests/contexts/cookie-compliance-context.bats
```

### CI/CD Testing

Tests run automatically in GitHub Actions on:
- Push to `main` or `dev` branches
- Pull requests
- Manual workflow dispatch

For detailed information about the testing infrastructure, including:
- Prerequisites and setup
- Test structure and organization
- How to add new tests
- Troubleshooting guide

See the **[Testing Documentation](tests/README.md)**.

## Contexts

### AntiSpam Context

Provides functionality to temporarily disable Honeypot time limits during test execution to prevent false negatives.

#### Steps

No manual steps. This context uses tags.

#### Tags

- **@honeypot-disable**: Automatically disables honeypot time limit before scenarios with this tag and restores it afterwards.

#### Configuration

No configuration needed.

#### Notes

- Requires Honeypot module to be installed
- Useful for preventing test failures caused by Honeypot's time-based form submission restrictions
- Temporarily sets honeypot time limit to 0 and restores the original value after the scenario

### Cache context

Steps to clear caches.

#### Steps

- Given :path page cache is flushed

  Clear specific page caches.

- Given :view view data cache is flushed

  Clear caches for a specific view. Only available for Drupal 7, not yet implemented in D8.

#### Configuration

No configuration needed.

### Content authored context

Allows creating content owned by the logged-in user.

#### Steps

- Given own :type content:

  Create content with the author as the current user.

#### Configuration

No configuration needed.

### Cron context

Helpers to execute cron.

#### Steps

- Given I run elysia cron

  Runs Elysia cron. Only for D7.

- Given I run the elysia cron :job job

  Runs the specified Elysia cron job. Only for D7.

- Given I run the cron of Search API

  Runs Search API cron. Only for D7.

- Given I run the cron of Search API Solr

  Runs Search API Solr cron. Only for D7.

#### Configuration

No configuration needed.

### Cookie compliance context

It allows to check that sites are GDPR-compliant with regard to cookies.
This feature is compatible with any cookie banner integration with the proper configuration. The context includes preconfigurations for OneTrust, Klaro and EU Cookie Compliance Drupal modules.

It can check that there are no cookies saved in the browser before they are accepted. It can also check the expected cookies appear when cookies are accepted.

The context has a default list of domains of typical third party services that may add cookies to the browser, but this list is not exhaustive. Check your site and add any additional domains you may need to the *cookies_third_party_domains_included* parameter.

By default, only those iframes whose domains belong to the **THIRD_PARTY_COOKIE_HOSTS** CookieComplianceContext constant will be detected as iframes that will add unwanted cookies.

There are two main ways to use this context: using one of the cookie managers supported (OneTrust, Klaro and EU Cookie Compliance Drupal module), or configuring all the parameters manually.

For a supported provider, just set the **cookie_manager_type** parameter to the desired value:
  - onetrust
  - klaro
  - eu_cookie_compliance

Example configuration *with* Cookie Manager type:
```yaml
  - CookieComplianceContext:
      cookie_manager_type: onetrust
      ...
      ...
```

If you are using an unsupported cookie manager or if for whatever reason you want to configure the parameters manually, you can set parameters you need as shown in the example below.

  - **cookie_agree_selector**: the CSS selector of the button to accept the default cookies.
  - **cookie_reject_selector**: the CSS selector of the button to reject all cookie categories.
  - **cookie_banner_selector**: the CSS selector of the cookie compliance banner.
  - **cookies**: maps cookies to cookie categories. The key is the cookie category and the value is the list of cookies that will be present after
    accepting that category in the cookie banner.
  - **cookies_ignored**: list of cookies that won't be taken into account when checking
  if cookies have been loaded.
  - **cookies_third_party_domains_included**: additional domains to check for third party cookies apart from the default list (see CookieComplianceContext::THIRD_PARTY_COOKIE_HOSTS).
  - **cookies_third_party_domains_ignored**: domains to ignore when checking for third party cookies. This allows to ignore domains that are included in the default list (see CookieComplianceContext::THIRD_PARTY_COOKIE_HOSTS).


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

Additionally, you can enable debug mode by setting the *debug* parameter to true. This will print the cookies present in the browser after accepting or rejecting cookies.

```yaml
  - CookieComplianceContext:
      ...
      ...
      debug: true
```

#### Steps

- **Then I accept cookies**: accept cookies by clicking the accept button in cookie popup or banner.

- **Then I reject cookies**: reject cookies by clicking the reject button in cookie popup or banner.

- **Then the cookies of :type type have not been loaded**: assert the cookies of a specific category are not present.

- **Then the cookies of :type type have been loaded**:  assert the cookies of a specific category are present.

- **When I wait for the cookie banner to appear**:  wait until the cookie banner is loaded.

- **Then there should not be any cookies loaded**:  check there are no cookies loaded at all. It also reports potential cookie source coming from third party iframes (e.g.: YouTube, DoubleClick, etc.).

- **Given the cookie with name :cookie_name exists**:  check if the cookie exists.

- **Given the cookie with name :cookie_name exists with value :value**: check that the cookie exists with the specific value.


#### Tags

- **@cookies-accepted**: accept cookies automatically adding the tag to the test.

- **@cookies-rejected**: reject cookies automatically adding the tag to the test.


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

- Then I wait for :seconds second(s)

  Halts test for a given amount of seconds. Useful when debugging tests with timing issues. Don't use this step in real tests.


#### Configuration
  Add DebugContext to your suite.

  This is an example when bootstrap directory is in DRUPALROOT/sites/all/tests/behat/bootstrap.

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
  - error_reporting_url: Url where the error screenshots will be shown. As we can see in the example, the url must point to the directory where we save the reports, and the directory must be accessible through the website.
  - screenshots_path: Path where screenshots are saved. Report screenshots are saved in the report path, here only screenshots from _capture full page_ steps are saved.
  - page_contents_path: Path where page contents are saved. Report page contents are saved in the report path, here only page contents from _save page content_ steps are saved.

### Drupal Groups Extended Context

Utilities for testing with Drupal Group module.

#### Steps

- Given the user :user_name is the owner of the group type :group_type with name :group_name

  Sets the owner of a group to a specific user.

- Given user :user is subscribed to the group of type :group_type with name :name

  Subscribes a user to a group as a member.

- Given content :title with bundle :bundle is subscribed to the group of type :group_type with name :name

  Adds content (node) to a group.

- Given user :user is subscribed to the group of type :group_type with name :name as a(n) :role role(s)

  Subscribes a user to a group with specific group roles. Multiple roles can be specified with comma separation.

#### Configuration

No configuration needed.

#### Notes

- Requires Drupal Group module (`drupal/group`)
- For Drupal 7 Organic Groups, use `DrupalOrganicGroupsExtendedContext` instead
- Groups, users, and content must already exist before using these steps

### Drupal Organic Groups Extended Context

Utilities for testing with Drupal 7 Organic Groups module.

#### Steps

- Given user :user is subscribed to the group of type :group_type with name :name

  Subscribes a user to a group of any entity type.

- Given user :user is subscribed to the group of type :group_type with name :name as a(n) :role role(s)

  Subscribes a user to a group with specific organic group roles. Multiple roles can be specified with comma separation.

- Given user :user is subscribed to the group with name :name

  Subscribes a user to a node-type group (shortcut for entity_type='node').

- Given user :user is subscribed to the group with name :name as a(n) :role role(s)

  Subscribes a user to a node-type group with specific roles (shortcut for entity_type='node').

#### Configuration

No configuration needed.

#### Notes

- Drupal 7 only - requires Organic Groups module
- For Drupal 8+ Group module, use `DrupalGroupsExtendedContext` instead
- Groups must already exist and group roles must be configured before using these steps

### Entity Context

Agnostic steps related to entities.

#### Steps

- Given I go to the last entity :entity created

  Go to last entity created.

- Given I go to the last entity :entity with :bundle bundle created

  Go to the last entity created from a specific bundle.

- Given I go to :subpath of the last entity :entity created

  Go to last entity created subpath (e.g.: node/1/edit).

- Given I go to :subpath of the last entity :entity with :bundle bundle created

  Go to last entity created subpath (e.g.: node/1/edit) from a specific bundle.

#### Configuration

No configuration needed.

### File context

Create files in Drupal.

#### Steps

- Given file with name :filename

  Create file in Drupal file system. Files are extracted from `files_path` set in Behat configuration.

- Given file with name :filename in the :directory directory

  Create file in Drupal file system in a specific directory. Directory must start with file system (public:// , private://). Default is public:// .

#### Configuration

Configure the `files_path` parameter in your `behat.yml` to specify where test files are located.

### Form Context

Steps for form elements.

#### Steps

- Then form :type element :label should be required

  Check a form element of a specific type (e.g.: input, select) with label is required.

- Then form :type element :label should not be required

  Check a form element of a specific type (e.g.: input, select) with label isn't required.

#### Configuration

No configuration needed.

### I18n Context

Internationalization steps for testing multilingual sites, allowing interaction with translated interface elements.

When steps reference "translated" fields, buttons, pages, or text, they use Drupal's translation system to find the translated label or text from the provided text.

#### Steps

- When I fill in :field translated field with :value

  Fills in a form field using the translated label based on current page language.

- When I fill in :value for :field translated field

  Alternative syntax to fill in a form field using translated field label.

- When I press the :button translated button

  Presses a button using its translated label.

- Given I press :button translated button in the :region( region)

  Presses a button in a specific region using its translated label.

- Then I (should )see the translated text :text

  Asserts that the translated version of the text is visible on the page.

- Then I should be on :path translated page

  Checks the current page path matches the expected path after translation.

#### Configuration

No configuration needed.

#### Notes

- Automatically detects current page language from the HTML `lang` attribute
- Uses Drupal's translation system to translate provided text in steps.
- Useful for testing multilingual forms and UI elements without hardcoding translations
- Requires multilingual site with language detection and interface translations configured

### Logs Context

Monitors and reports watchdog (dblog) errors during Behat test execution.

#### Steps

No manual steps. This context automatically displays logs after scenarios and provides a summary after the suite.

#### Functionality

- Displays watchdog logs generated during each scenario
- Shows a summary table after the suite with all logs grouped by message
- Optionally generates CSV reports with detailed log information

#### Configuration

```yaml
- Metadrop\Behat\Context\LogsContext:
    parameters:
      base_url: 'http://example.docker.localhost:8000'
      types:
        - php
        - cron
      levels:
        - ERROR
        - WARNING
        - NOTICE
      limit: 100
      path: '/var/www/html/reports/behat/dblog'
      write_report: false
```

**Parameters:**
- `base_url` (optional): Base URL for generating links to log event details
- `types` (optional, default: `['php']`): Array of log types to monitor
- `levels` (optional, default: `['ERROR', 'WARNING', 'NOTICE']`): Log severity levels to capture. Available levels: EMERGENCY, ALERT, CRITICAL, ERROR, WARNING, NOTICE, INFO, DEBUG
- `limit` (optional, default: `100`): Maximum number of log entries in the after-suite table
- `path` (optional, default: `DRUPAL_ROOT . '/../reports/behat/dblog'`): Path for CSV reports
- `write_report` (optional, default: `false`): Enable/disable CSV report generation

#### Notes

- Requires dblog (Database Logging) module to be enabled
- Logs are captured from suite start time
- CSV report includes: Index, Type, Severity, Message, Location, Referer, Link, Details URL, Total Messages
- For failed scenarios, all log types are shown; for passing scenarios, only configured types
- Messages are truncated to 200 characters in display output

### Media Context

Steps for working with Drupal Media entities through the media library widget.

#### Steps

- Then I upload media with name :media_title to :field field using :widget widget

  Selects an existing media item from the media library and assigns it to a field.

- Then I assign the media with name :media_title to :field field

  Alternative syntax to assign existing media to a field (uses media_library widget).

- Then I should see the :media_type media with name :media_title

  Verifies that media is visible on the page. Supports 'image' and 'document' media types.

#### Configuration

No configuration needed.

#### Notes

- Requires Drupal Media and Media Library modules
- Currently only supports the 'media_library' widget
- Media entities must already exist before assigning them (doesn't upload new files)
- Requires JavaScript driver (Selenium) for AJAX interactions
- Depends on UIContext and WaitingContext
- For images: checks img src attribute; For documents: checks link href attribute

### Node Access context

Steps related to the node access system. Only for D7.

#### Steps

- Given the access of last node created is refreshed

  Refresh node grants from the last node.

- Given the access of last node created with :bundle bundle is refreshed

  Refresh node grants from the last node of a specific content type.

#### Configuration

No configuration needed.

### Paragraphs context

Steps to attach paragraphs to content.

#### Steps

- Given paragraph of :paragraph_type type referenced on the :field_paragraph field of the last content:

  Create a paragraph with fields and attach it to the last node created.

#### Configuration

No configuration needed.

### Search API Context

Automatically indexes content in Search API immediately after entity creation during tests.

#### Steps

No manual steps. This context automatically indexes entities using hooks.

#### Functionality

- Forces immediate indexing of nodes after creation via Behat steps
- Indexes parent entities when child entities (like paragraphs) are created
- Ensures search results are available without waiting for cron

#### Configuration

No configuration needed.

#### Notes

- Requires Search API module
- Works with all entity types tracked by Search API
- Handles all translations of entities
- Includes a small delay (300ms) after indexing to allow cache tag invalidation
- Automatically called after entity creation - no manual steps needed
- Essential for tests that depend on search functionality

### Url Context

Steps to check url values

#### Steps

- Then current url should have the ":param" param with ":value" value

  Check an url has a specific value in a query parameter.

- Then current url should not have the ":param" param with ":value" value

  Check an url hasn't a specific value in a query parameter.

#### Configuration

No configuration needed.


### UIContext

This context provides steps for certain UI elements.

#### Steps

- Given I select :option from :select chosen.js select box

  Selects an option from a Chosen select widget. Only for single selection, it
  doesn't work with multiple selection enabled or tag style.

  See https://harvesthq.github.io/chosen/

#### Configuration

No configuration needed.

#### Advanced usage

**Using elementShouldBeInPosition method**

The UIContext provides an `elementShouldBeInPosition` method that can be used to verify element positions in lists or grids. This is useful for testing sorting, ordering, or layout functionality.

Example implementation in a custom step:

```php
/**
 * Example of implementation elementShouldBeInPosition on a custom step.
 *
 * @Then the card on the infinite scroll view with title :title should be in position :position.
 */
public function theCardWithTitleShouldBeInPositionExample(string $title, string $position) {
  $this->elementShouldBeInPosition('item-list-css-selector', $title, 'views-infinite-scroll-content-wrapper', $position);
}
```

### Users Context

Context for user-related operations and assertions. Provides steps to verify user existence, check user roles, and authenticate as users with specific roles.

#### Steps

- Then the user with mail :mail exists

  Check that a user with the specified email address exists in the system.

- Then user with the email address :mail does not exist

  Check that a user with the specified email address does not exist in the system.

- Then I should have the :role role(s)

  Check the current user has specific role(s). The role parameter can be a single role or comma-separated list of roles.

- Then the user :user should have the :role role(s)

  Check a specified user has specific role(s). The role parameter can be a single role or comma-separated list of roles. User parameter can be a username, email, or uid.

- Then I should not have the :role role(s)

  Check the current user does not have specific role(s). The role parameter can be a single role or comma-separated list of roles.

- Then the user :user should not have the :role role(s)

  Check a specified user does not have specific role(s). The role parameter can be a single role or comma-separated list of roles. User parameter can be a username, email, or uid.

- Given I am a user with :role role

  Authenticate as a user with a specific role. If role is 'anonymous', logs out the current user. Otherwise, creates and logs in as a user with the specified role.

#### Configuration

No configuration needed.

### Users Random Context

Context used to generate random user data (username, email, password) for testing purposes. This context does NOT create actual Drupal users - it only generates random user data that can be used to fill forms during tests. This is particularly useful for testing interactions with remote APIs that require unique values on each test run and cannot clean previous data.

#### Steps

- Given random users identified by:

  Generate random user data (email, username, and password) that can be referenced in later steps. Requires a table with an 'identifier' column. Do not use spaces or special characters in identifiers.

  Example:
  ```gherkin
  Given random users identified by:
    | identifier  |
    | debug       |
    | email_test2 |
  ```

- Then I fill in :field with random email from :random_user_identifier

  Fill a form field with the random email from a previously generated random user.

- Then I fill in :field with random email from :random_user_identifier in the :region( region)

  Fill a form field in a specific region with the random email from a previously generated random user.

- Then I fill in :field with random username from :random_user_identifier

  Fill a form field with the random username from a previously generated random user.

- Then I fill in :field with random username from :random_user_identifier in the :region( region)

  Fill a form field in a specific region with the random username from a previously generated random user.

- Then I fill in :field with random password from :random_user_identifier

  Fill a form field with the random password from a previously generated random user.

- Then I fill in :field with random password from :random_user_identifier in the :region( region)

  Fill a form field in a specific region with the random password from a previously generated random user.

#### Configuration

No configuration needed.

#### Usage Example

```gherkin
Scenario: Register user via external API
  Given random users identified by:
    | identifier |
    | new_user   |
  When I visit "/register"
  And I fill in "Username" with random username from "new_user"
  And I fill in "Email" with random email from "new_user"
  And I fill in "Password" with random password from "new_user"
  And I press "Submit"
  Then I should see "Registration successful"
```

#### Important Notes

- Random user identifiers should not contain spaces or special characters.
- Random email format: `{identifier}+{uuid}@metadrop.net`
- Random username format: `{identifier}_{uuid}`
- Random password format: `{identifier}_{uuid}`
- You must generate random users with the "Given random users identified by:" step before using them in other steps.
- This context does NOT create actual Drupal user entities - it only generates data for form filling.
- Useful for testing scenarios where the same user data cannot be used multiple times (e.g., external API integrations).

### WaitingContext

This context provides waiting time after defined steps, and extra waiting steps.

Waiting steps - Sometimes steps are running faster than our site, if this is the case you can delay them a few seconds. Don't abuse this functionality, if Behat is running slow maybe there is a performance global site issue that needs to be solved first!

#### Steps

    - Then I wait for :seconds second(s)
      Step waits for a defined number of seconds before executing the next step.


#### Configure waiting time before executing the next step

Set in `behat.yml` the step action with wait time in seconds before executing the next step.

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
      enabled: true
      show_test_info_screen: true
      show_test_info_screen_time: 2000
      show_green_screen: false
      show_green_screen_time: 1000
      show_step_info_bubble: true
      show_step_info_bubble_time: 2000
      show_error_info_bubble: true
      show_error_info_bubble_time: 2000
