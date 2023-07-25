<?php

/*
 * This file is part of the DevShop package.
 *
 * (c) Jon Pugh <jon@thinkdrop.net
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Operations\Composer\Plugin\GitSplit\Composer;

use Operations\Composer\Plugin\GitSplit\Command\GitSplitConsoleCommand;
use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;
use Operations\Composer\Plugin\GitSplit\Command\GitSplitComposerCommand;

class CommandProvider implements CommandProviderCapability
{
    public function getCommands()
    {
        return array(
          new GitSplitComposerCommand()
        );
    }
}
