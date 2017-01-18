<?php

use Incvisio\Validation;
use Incvisio\SSO\Server as Server;

/**
 * Example SSO server.
 *
 * Normally you'd fetch the service info and user info from a database, rather then declaring them in the code.
 */
class SSOServer extends Server
{
    /**
     * Registered services
     * @var array
     */
    private static $services = [
        'server1' => ['secret'=>'8iwzik1bwd'],
        'server2' => ['secret'=>'7pypoox2pc'],
        'server3' => ['secret'=>'129889asfbjasbf']
    ];

    /**
     * System users
     * @var array
     */
    private static $users = array (
        'max' => [
            'fullname' => 'Max Admaykin',
            'email' => 'max.admaykin@example.com',
            'password' => '$2y$10$lVUeiphXLAm4pz6l7lF9i.6IelAqRxV4gCBu8GBGhCpaRb6o0qzUO' // jackie123
        ],
        'max2' => [
            'fullname' => 'Max Admaykin 2',
            'email' => 'max2@example.com',
            'password' => '$2y$10$RU85KDMhbh8pDhpvzL6C5.kD3qWpzXARZBzJ5oJ2mFoW7Ren.apC2' // john123
        ],
    );

    /**
     * Get the API secret of a service and other info
     *
     * @param string $serviceId
     * @return array
     */
    protected function getServiceInfo($serviceId)
    {
        return isset(self::$services[$serviceId]) ? self::$services[$serviceId] : null;
    }

    /**
     * Authenticate using user credentials
     *
     * @param string $username
     * @param string $password
     * @return Validation
     */
    protected function authenticate($username, $password)
    {
        if (!isset($username)) {
            return Validation::error("username isn't set");
        }

        if (!isset($password)) {
            return Validation::error("password isn't set");
        }

        if (!isset(self::$users[$username]) || !password_verify($password, self::$users[$username]['password'])) {
            return Validation::error("Invalid credentials");
        }

        return Validation::success();
    }


    /**
     * Get the user information
     *
     * @return array
     */
    protected function getUserInfo($username)
    {
        if (!isset(self::$users[$username])) return null;

        $user = compact('username') + self::$users[$username];
        unset($user['password']);

        return $user;
    }
}
