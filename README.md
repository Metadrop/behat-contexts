# Behat Contexts


Contexts that we use with Behat 3.x tests on Drupal sites.

## Install

This contexts use the Drupal Extension [Drupal Extension](https://www.drupal.org/project/drupalextension), so it should be installed in your system.

Put this repository in the bootstrap directory of your project. A Drupal 7 project using Drupal Extension this directory may be located in sites/all/tests/behat/bootstrap. So, directory Metadrop of this repository should be in that bootstrap directory. Contexts will be autoladed.

@TODO: Improve installation method, use some standard way.

## Configure

Each context may have its own configuration, see each context section.

## Contexts

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

 - Then save a capture full page with width of :width to :path

   Saves a screenshot of current page with the given width to a file in the given path.

- Then save a capture full page with width of :width to :path with name :filename

   Saves a screenshot of current page with the given width to a file in the given path to a given filename.

 - Then save last response

   Saves page content to a file.

 - Then save last response to :path

   Saves page content to a file in the given path.

#### Configuration
  Add DebugContext to your suite

  This is an example when bootstrap directorty is in DRUPALROOT/sites/all/tests/behat/bootstrap.

```
default:
  autoload:
    '': "%paths.base%/../all/tests/behat/bootstrap"
  suites:
    default:
      paths:
        - "%paths.base%/../all/tests/behat/features"
      contexts:
        - Metadrop\Behat\Context\DebugContext:
            parameters:
              'report_on_error': true
              'error_reporting_path': '/var/error_reports/tests/reports'
              'screenshots_path': '/var/error_reports/tests/screenshots'
              'page_contents_path': '/var/error_reports/tests/pages'
```

**Parameters**
  - report_on_error: If _true_ error reports are generated on failed steps.
  - error_reporting_path: Path where reports are saved.
  - screenshots_path: Path where screenshots are saved. Report screenshots are saved in the report path, here only screenshots from _capture full page_ steps are saved.
  - page_contents_path: Path where page contents are saved. Report page contents are saved in the report path, here only page contents from _save page content_ steps are saved.


