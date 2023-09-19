<?php

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\DrupalExtension\Context\DrupalContext;
use Drupal\DrupalExtension\Context\DrushContext;
use Behat\MinkExtension\Context\MinkContext;

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Tester\Exception\PendingException;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends RawDrupalContext {

  /**
   * @var \Symfony\Component\Console\Style\SymfonyStyle
   */
  protected $io;
  /**
   * Provides helpers to operate on files and stream wrappers.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Make MinkContext available.
   * @var \Drupal\DrupalExtension\Context\MinkContext
   */
  private $minkContext;

  /**
   * Make DrupalContext available.
   * @var \Drupal\DrupalExtension\Context\DrupalContext
   */
  private $drupalContext;

  /**
   * Make MinkContext available.
   * @var \Drupal\DrupalExtension\Context\DrushContext
   */
  private $drushContext;

  /**
   * @var array List of URLs the link test step has clicked.
   */
  private $visitedLinks = array();

  /**
   * Prepare Contexts.
   * @BeforeScenario
   */
  public function gatherContexts(\Behat\Behat\Hook\Scope\BeforeScenarioScope $scope)
  {
    $environment = $scope->getEnvironment();
    $this->minkContext = $environment->getContext('Drupal\DrupalExtension\Context\MinkContext');
    $this->drupalContext = $environment->getContext('Drupal\DrupalExtension\Context\DrupalContext');
    $this->drushContext = $environment->getContext('Drupal\DrupalExtension\Context\DrushContext');
    $this->fileSystem = \Drupal::service('file_system');

    $input = new Symfony\Component\Console\Input\ArgvInput();

    $output = new \Symfony\Component\Console\Output\ConsoleOutput();
    $output->setDecorated(TRUE);
    $this->io = new \Symfony\Component\Console\Style\SymfonyStyle($input, $output);

  }

  /**
   * Initializes context.
   *
   * Every scenario gets its own context instance.
   * You can also pass arbitrary arguments to the
   * context constructor through behat.yml.
   */
  public function __construct() {
  }

  /**
   * Log output and watchdog logs after any failed step.
   * @AfterStep
   */
  public function logAfterFailedStep(\Behat\Behat\Hook\Scope\AfterStepScope $event)
  {
    if ($event->getTestResult()->getResultCode() === \Behat\Testwork\Tester\Result\TestResult::FAILED) {

      $base_url = $this->getMinkParameter('base_url');
      $drush_config = $this->drupalContext->getDrupalParameter('drush');
      $alias = ltrim($drush_config['alias'], '@');

      $test_artifacts = 'public://test_artifacts';
      $failure_file_path = 'public://test_artifacts/failure-'. time() . '.html';

      // Create and save output to a file.
      $this->fileSystem->prepareDirectory($test_artifacts, $this->fileSystem::CREATE_DIRECTORY);
      $this->fileSystem->saveData($this->getSession()->getPage()->getContent(), $failure_file_path, $this->fileSystem::EXISTS_REPLACE);

      // @see FileRepository::createOrUpdate().
      $new_file = \Drupal\file\Entity\File::create(['uri' => $failure_file_path]);
      $new_file->setOwnerId(1);
      $new_file->setPermanent();
      $new_file->save();

      \Drupal::service('file_url_generator')->generate('public://basename.ext');
      /** @var  \Drupal\Core\Url */
      $file_url = \Drupal::service('file_url_generator')->generate($failure_file_path);
      $file_path = \Drupal::service('file_system')->realpath($failure_file_path);

      // Watchdog
      $command = 'wd-show';
      $watchdogs = $this->getDriver('drush')->$command();

      $step = $event->getStep()->getText();
      $this->io->error("Step Failed: $step");

      $this->io->table([], [
        ['Last URL: ', $this->getSession()->getCurrentUrl()],
        ['Drush Alias: ', $alias],
        ['Last page output: ', $file_url->setOption('absolute', true)->toString()],
        ['', $file_path],
      ]);

      $this->io->write($watchdogs);


    }
  }


  /**
   * @Given I am logged in as user :name
   */
  public function iAmLoggedInAsUser($name) {
    $domain = $this->getMinkParameter('base_url');

    // Pass base url to drush command.
    $uli = $this->getDriver('drush')->drush('uli', [
      "--name '" . $name . "'",
      "--browser=0",
      "--uri=$domain",
    ]);

    // Trim EOL characters.
    $uli = trim($uli);

    // Log in.
    $this->getSession()->visit($uli);
  }
}
