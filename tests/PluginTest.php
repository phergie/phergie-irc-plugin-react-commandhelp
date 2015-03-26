<?php
/**
 * Phergie (http://phergie.org)
 *
 * @link https://github.com/phergie/phergie-irc-plugin-react-commandhelp for the canonical source repository
 * @copyright Copyright (c) 2008-2014 Phergie Development Team (http://phergie.org)
 * @license http://phergie.org/license Simplified BSD License
 * @package Phergie\Irc\Plugin\React\CommandHelp
 */

namespace Phergie\Irc\Tests\Plugin\React\CommandHelp;

use Phake;
use Phergie\Irc\Bot\React\EventQueueInterface;
use Phergie\Irc\Bot\React\PluginInterface;
use Phergie\Irc\Plugin\React\Command\CommandEvent;
use Phergie\Irc\Plugin\React\CommandHelp\Plugin;

/**
 * Tests for the Plugin class.
 *
 * @category Phergie
 * @package Phergie\Irc\Plugin\React\CommandHelp
 */
class PluginTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Data provider for testInvalidConfiguration().
     *
     * @return array
     */
    public function dataProviderInvalidConfiguration()
    {
        $data = array();
        $plugin = $this->getMockPlugin();

        $data[] = array(
            array(
                'plugins' => 'foo',
            ),
            Plugin::ERR_PLUGINS_NONARRAY,
        );

        $data[] = array(
            array(
                'plugins' => array(new \stdClass),
            ),
            Plugin::ERR_PLUGINS_NONPLUGINS,
        );

        $data[] = array(
            array(
                'plugins' => array($plugin, new \stdClass),
            ),
            Plugin::ERR_PLUGINS_NONPLUGINS,
        );

        return $data;
    }

    /**
     * Tests instantiating the class under test with invalid configuration.
     *
     * @param array $config Configuration to apply
     * @param int $code Expected exception code
     * @dataProvider dataProviderInvalidConfiguration
     */
    public function testInvalidConfiguration(array $config, $code)
    {
        try {
            $plugin = new Plugin($config);
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertSame($code, $e->getCode());
        }
    }

    /**
     * Tests that the plugin emits an event when help is requested for a
     * specific command.
     */
    public function testHandleHelpCommandEmitsEvent()
    {
        $eventEmitter = $this->getMockEventEmitter();

        $plugin = new Plugin;
        $plugin->setEventEmitter($eventEmitter);

        $event = $this->getMockCommandEvent();
        Phake::when($event)->getCustomParams()->thenReturn(array('foo'));
        $queue = $this->getMockEventQueue();

        $plugin->handleHelpCommand($event, $queue);

        Phake::verify($eventEmitter)
            ->emit('command.foo.help', array($event, $queue));
    }

    /**
     * Data provider for testHandleHelpCommandListsCommands().
     *
     * @return array
     */
    public function dataProviderHandleHelpCommandListsCommands()
    {
        $data = array();
        $data[] = array('#channel', '#channel', 'user: Available commands: ');
        $data[] = array('bot', 'user', 'Available commands: ');
        $data[] = array('#channel', '#channel', 'user: Commands: ', 'Commands: ');
        $data[] = array('bot', 'user', 'Commands: ', 'Commands: ');
        return $data;
    }

    /**
     * Tests listing available commands.
     *
     * @param string $requestTarget
     * @param string $responseTarget
     * @param string $address
     * @param string|null $listText
     * @dataProvider dataProviderHandleHelpCommandListsCommands
     */
    public function testHandleHelpCommandListsCommands($requestTarget, $responseTarget, $address, $listText = null)
    {
        $foo = $this->getMockPlugin();        
        Phake::when($foo)
            ->getSubscribedEvents()
            ->thenReturn(array('command.foo' => 'handleFoo', 'command.foo.help' => 'handleFooHelp'));
        $bar = $this->getMockPlugin();
        Phake::when($bar)
            ->getSubscribedEvents()
            ->thenReturn(array('command.bar' => 'handleBar', 'command.bar.help' => 'handleBarHelp'));

        $connection = $this->getMockConnection();
        Phake::when($connection)->getNickname()->thenReturn('bot');

        $event = $this->getMockCommandEvent();
        Phake::when($event)->getConnection()->thenReturn($connection);
        Phake::when($event)->getCommand()->thenReturn('PRIVMSG');
        Phake::when($event)->getTargets()->thenReturn(array($requestTarget));
        Phake::when($event)->getNick()->thenReturn('user');
        $queue = $this->getMockEventQueue();

        $plugin = new Plugin(array(
            'plugins' => array($foo, $bar),
            'listText' => $listText,
        ));
        $plugin->handleHelpCommand($event, $queue);

        Phake::verify($queue)
            ->ircPrivmsg($responseTarget, $address . 'foo bar');
    }

    /**
     * Tests that getSubscribedEvents() returns an array.
     */
    public function testGetSubscribedEvents()
    {
        $plugin = new Plugin;
        $this->assertInternalType('array', $plugin->getSubscribedEvents());
    }

    /**
     * Returns a mock plugin.
     *
     * @return \Phergie\Irc\Bot\React\PluginInterface
     */
    protected function getMockPlugin()
    {
        $plugin = Phake::mock('\Phergie\Irc\Bot\React\PluginInterface');
        Phake::when($plugin)->getSubscribedEvents()->thenReturn(array());
        return $plugin;
    }

    /**
     * Returns a mock command event.
     *
     * @return \Phergie\Irc\Plugin\React\Command\CommandEvent
     */
    protected function getMockCommandEvent()
    {
        $event = Phake::mock('\Phergie\Irc\Plugin\React\Command\CommandEvent');
        Phake::when($event)->getCustomParams()->thenReturn(array());
        return $event;
    }

    /**
     * Returns a mock event queue.
     *
     * @return \Phergie\Irc\Bot\React\EventQueueInterface
     */
    protected function getMockEventQueue()
    {
        return Phake::mock('\Phergie\Irc\Bot\React\EventQueueInterface');
    }

    /**
     * Returns a mock event emitter.
     *
     * @return \Evenement\EventEmitterInterface
     */
    protected function getMockEventEmitter()
    {
        return Phake::mock('\Evenement\EventEmitterInterface');
    }

    /**
     * Returns a mock connection.
     *
     * @return \Phergie\Irc\ConnectionInterface
     */
    protected function getMockConnection()
    {
        return Phake::mock('\Phergie\Irc\ConnectionInterface');
    }
}
