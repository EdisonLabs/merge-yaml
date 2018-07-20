<?php

namespace EdisonLabs\MergeYaml\Unit;

use EdisonLabs\MergeYaml\Plugin;
use Composer\Script\ScriptEvents;
use PHPUnit\Framework\TestCase;

/**
 * Tests for EdisonLabs\MergeYaml\Plugin
 */
class PluginTest extends TestCase
{
    /**
     * Tests for EdisonLabs\MergeYaml\Plugin
     */
    public function testPlugin()
    {
        $plugin = new Plugin();

        $capabilities = $plugin->getCapabilities();
        $this->assertEquals(['Composer\Plugin\Capability\CommandProvider' => 'EdisonLabs\MergeYaml\CommandProvider'], $capabilities);

        $events = $plugin->getSubscribedEvents();
        $this->assertCount(2, $events);
        $this->assertArrayHasKey(ScriptEvents::POST_INSTALL_CMD, $events);
        $this->assertArrayHasKey(ScriptEvents::POST_UPDATE_CMD, $events);
        $this->assertEquals(['postCmd', -1], $events[ScriptEvents::POST_INSTALL_CMD]);
        $this->assertEquals(['postCmd', -1], $events[ScriptEvents::POST_UPDATE_CMD]);
    }
}
