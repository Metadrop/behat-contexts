# Behat Contexts


Contexts that we use with Behat 3.x tests on Drupal sites.

This repository is based on [Nuvole drupal extension](https://github.com/nuvoleweb/drupal-behat).

## Install

Install with [Composer](http://getcomposer.org) with the following command:

`composer require metadrop/behat-contexts`

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


### IUContext

  This context provides steps for certain UI elements.


#### Steps

- Given I select :option from :select chosen.js select box

  Selects and option from a Chosen select widget. Only for sinlge selection, it
  doesn't work with multiple selection enabled or tag style.

  See https://harvesthq.github.io/chosen/


#### Configuration

No configuration needed.
