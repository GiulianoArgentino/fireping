<?php
/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 9/06/2017
 * Time: 12:38
 */

namespace AppBundle\ShellCommand;

class CommandFactory
{
    protected static $mappings = array(
        'ping' => 'AppBundle\\ShellCommand\\PingShellCommand',
        'mtr' => 'AppBundle\\ShellCommand\\MtrShellCommand',
        'traceroute' => 'AppBundle\\ShellCommand\\TracerouteShellCommand',
        'config-sync' => 'AppBundle\\ShellCommand\\GetConfigHttpWorkerCommand',
        'post-result' => 'AppBundle\\ShellCommand\\PostResultsHttpWorkerCommand',
    );

    public function create($command, $args)
    {
        if (!isset(self::$mappings[$command])) {
            throw new \Exception("No mapping exists for command $command.");
        }

        $class = self::$mappings[$command];
        return new $class($args);
    }
}