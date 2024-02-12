<?php

namespace Operations;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\BinaryInstaller;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

class ProjectBinPlugin implements PluginInterface, EventSubscriberInterface
{
  /**
   * The Composer service.
   *
   * @var \Composer\Composer
   */
  protected $composer;

  /**
   * Composer's I/O service.
   *
   * @var \Composer\IO\IOInterface
   */
  protected $io;

  public function activate(Composer $composer, IOInterface $io)
  {
    $this->composer = $composer;
    $this->io = $io;
  }

  public function deactivate(Composer $composer, IOInterface $io)
  {
  }

  public function uninstall(Composer $composer, IOInterface $io)
  {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Important note: We only instantiate our handler on "post" events.
    return [
      ScriptEvents::POST_INSTALL_CMD => 'installProjectBins',
    ];
  }

  public function installProjectBins(Event $event)
  {
    $config = $this->composer->getPackage()->getConfig();
    $installer = new BinaryInstaller($this->io, $config['bin-dir'], 'auto');
    $this->io->notice('Installing local bins...');

    // This is the same code that installs dependency bin scripts.
    // It just runs on the parent project.
    $installer->installBinaries($this->composer->getPackage(), getcwd());
  }
}
