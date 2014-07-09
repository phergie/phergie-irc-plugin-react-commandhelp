# phergie/phergie-irc-plugin-react-commandhelp

[Phergie](http://github.com/phergie/phergie-irc-bot-react/) plugin for providing usage information for available bot commands to users.

This plugin is intended to complement the [Command plugin](https://github.com/phergie/phergie-irc-plugin-react-command).

[![Build Status](https://secure.travis-ci.org/phergie/phergie-irc-plugin-react-commandhelp.png?branch=master)](http://travis-ci.org/phergie/phergie-irc-plugin-react-commandhelp)

## Install

The recommended method of installation is [through composer](http://getcomposer.org).

```JSON
{
    "require": {
        "phergie/phergie-irc-plugin-react-commandhelp": "dev-master"
    }
}
```

See Phergie documentation for more information on
[installing and enabling plugins](https://github.com/phergie/phergie-irc-bot-react/wiki/Usage#plugins).

## Configuration

```php
new \Phergie\Irc\Plugin\React\CommandHelp\Plugin(array(

    // All configuration is optional

    // List of plugins that subscribe to command events
    // Used to return a list of available commands to users
    // All elements must implement \Phergie\Irc\Bot\React\PluginInterface
    'plugins' => array(
        // ..
    ),

    // Text to precede the list of available commands when providing it to
    // users
    'listText' => 'Available commands: ',

))
```

## Usage

This plugin provides a "help" command that can be invoked with or without parameters.

If it is invoked without parameters, it will respond with a list of all
available commands, which is obtained from the value of the `'plugins'`
configuration setting based on the command events to which those plugins
subscribe.

If it is invoked with parameters, it will assume the first parameter to be a
command name and emit an event for the plugin supporting that command to
respond to.

For example, if the first parameter value is `'foo'`, the plugin will emit a
`'command.foo.help'` event that the plugin supporting the "foo" command can
subscribe to. This event's parameters will be an object implementing
[`CommandEventInterface`](https://github.com/phergie/phergie-irc-plugin-react-command/blob/master/src/CommandEventInterface.php)
(the event originally received by the CommandHelp plugin) and an object
implementing [`EventQueueInterface`](https://github.com/phergie/phergie-irc-bot-react/blob/master/src/EventQueueInterface.php)
(which the receiving plugin can use to respond with information about its "foo"
command to the user who invoked the "help" command).

## Tests

To run the unit test suite:

```
curl -s https://getcomposer.org/installer | php
php composer.phar install
./vendor/bin/phpunit
```

## License

Released under the BSD License. See `LICENSE`.
