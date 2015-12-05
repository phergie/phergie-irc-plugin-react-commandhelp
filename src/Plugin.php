<?php
/**
 * Phergie (http://phergie.org)
 *
 * @link https://github.com/phergie/phergie-irc-plugin-react-commandhelp for the canonical source repository
 * @copyright Copyright (c) 2008-2014 Phergie Development Team (http://phergie.org)
 * @license http://phergie.org/license Simplified BSD License
 * @package Phergie\Irc\Plugin\React\CommandHelp
 */

namespace Phergie\Irc\Plugin\React\CommandHelp;

use Phergie\Irc\Bot\React\AbstractPlugin;
use Phergie\Irc\Bot\React\PluginInterface;
use Phergie\Irc\Bot\React\EventQueueInterface;
use Phergie\Irc\Plugin\React\Command\CommandEvent;

/**
 * Plugin for providing usage information for available bot commands to users.
 *
 * @category Phergie
 * @package Phergie\Irc\Plugin\React\CommandHelp
 */
class Plugin extends AbstractPlugin
{
    /**
     * List of available commands
     *
     * @var array
     */
    protected $commands = array();

    /**
     * Text preceding a list of available commands
     *
     * @var string
     */
    protected $listText = 'Available commands: ';

    /**
     * Exception code used when 'plugins' is not an array
     */
    const ERR_PLUGINS_NONARRAY = 1;

    /**
     * Exception code used when 'plugins' contains non-plugin objects
     */
    const ERR_PLUGINS_NONPLUGINS = 2;

    /**
     * Accepts plugin configuration.
     *
     * Supported keys:
     *
     * plugins - an array of plugin instances that subscribe to command events,
     * used to return a list of available commands, optional
     *
     * listText - text preceding a listing of available commands, optional
     *
     * @param array $config
     */
    public function __construct(array $config = array())
    {
        if (isset($config['plugins'])) {
            $this->commands = $this->getCommands($config);
        }

        if (isset($config['listText'])) {
            $this->listText = $config['listText'];
        }
    }

    /**
     * Extracts command information from configuration.
     *
     * @param array $config
     */
    protected function getCommands(array $config)
    {
        if (!is_array($config['plugins'])) {
            throw new \RuntimeException(
                'Configuration "plugins" key must reference an array',
                self::ERR_PLUGINS_NONARRAY
            );
        }

        $plugins = array_filter(
            $config['plugins'],
            function($plugin) {
                return $plugin instanceof PluginInterface;
            }
        );
        if (count($plugins) != count($config['plugins'])) {
            throw new \RuntimeException(
                'All configuration "plugins" array values must implement \Phergie\Irc\Bot\React\PluginInterface',
                self::ERR_PLUGINS_NONPLUGINS
            );
        }

        $commands = array();
        foreach ($plugins as $plugin) {
            $events = array_keys($plugin->getSubscribedEvents());
            $commandEvents = array();
            foreach ($events as $event) {
                if (!preg_match('/^command\.(.+)\.help$/', $event, $match)) {
                    continue;
                }
                $commands[$match[1]] = true;
            }
        }

        return $this->alphabetize($commands);
    }

    /**
     * Indicates that the plugin monitors the help command event.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            'command.help' => 'handleHelpCommand',
        );
    }

    /**
     * Responds to the help command.
     *
     * @param \Phergie\Irc\Plugin\React\Command\CommandEvent $event
     * @param \Phergie\Irc\Bot\React\EventQueueInterface $queue
     */
    public function handleHelpCommand(CommandEvent $event, EventQueueInterface $queue)
    {
        $params = $event->getCustomParams();
        if ($params) {
            $command = strtolower(reset($params));
            $eventName = 'command.' . $command . '.help';
            $this->getEventEmitter()->emit($eventName, array($event, $queue));
        } else {
            $this->listCommands($event, $queue);
        }
    }

    /**
     * Responds to a parameter-less help command with a list of available
     * commands.
     *
     * @param \Phergie\Irc\Plugin\React\Command\CommandEvent $event
     * @param \Phergie\Irc\Bot\React\EventQueueInterface $queue
     */
    protected function listCommands(CommandEvent $event, EventQueueInterface $queue)
    {
        $targets = $event->getTargets();
        $target = reset($targets);
        $nick = $event->getNick();

        if ($target === $event->getConnection()->getNickname()) {
            $target = $nick;
            $address = '';
        } else {
            $address = $nick . ': ';
        }

        $method = 'irc' . $event->getCommand();
        $message = $address . $this->listText . implode(' ', $this->commands);
        $queue->$method($target, $message);
    }

    private function alphabetize( $commands )
    {
        $commandList = array_keys($commands);

        return sort( $commandList, SORT_NATURAL | SORT_FLAG_CASE);
    }
}
