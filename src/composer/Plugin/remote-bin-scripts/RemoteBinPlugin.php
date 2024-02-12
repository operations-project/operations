<?php

namespace Operations;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\BinaryInstaller;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Package\Package;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Composer\Util\HttpDownloader;
use Composer\Util\Silencer;

class RemoteBinPlugin implements PluginInterface, EventSubscriberInterface
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
      ScriptEvents::POST_INSTALL_CMD => 'installRemoteBins',
    ];
  }

  public function installRemoteBins(Event $event)
  {
    // Download bins.
    $downloader = new HttpDownloader($this->io, $this->composer->getConfig());

    if (!empty($this->composer->getPackage()->getExtra()['remote-scripts'])) {
      foreach ($this->composer->getPackage()->getExtra()['remote-scripts'] as $file => $url) {
        $this->io->notice("Installing <comment>$url</comment> to <comment>$file</comment> and making it exectutable...");
        $downloader->copy($url, $file);
        Silencer::call('chmod', $file, 0777 & ~umask());
      }
    }
  }
}
