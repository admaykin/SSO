Single Sign-On for PHP (SSO)
---

Incvisio\SSO is a relatively simply and straightforward solution for an single sign on (SSO) implementation. With SSO,
logging into a single website will authenticate you for all affiliate sites.

#### How it works

When using SSO, when can distinguish 3 parties:

* Client - This is the browser of the visitor
* Service - The website which is visited
* Server - The place that holds the user info and credentials

The service has an id and a secret. These are know to both the service and server.

When the client visits the service, it creates a random token, which is stored in a cookie. The service will then send
the client to the server, passing along the service's id and token. The server creates a hash using the service id, service
secret and the token. This hash is used to create a link to the users session. When the link is created the server
redirects the client back to the service.

The service can create the same link hash using the token (from the cookie), the service id and the service secret. When
doing requests, it passes that has as session id.

The server will notice that the session id is a link and use the linked session. As such, the service and client are
using the same session. When another service joins in, it will also use the same session.



## Installation

Install this library through composer

    composer require incvisio/sso

## Usage

#### Server

`Incvisio\SSO\Server` is an abstract class. You need to create a your own class which implements the abstract methods.
These methods are called fetch data from a data souce (like a DB).

```php
class SSOServer extends Incvisio\SSO\Server
{
    /**
     * Authenticate using user credentials
     *
     * @param string $username
     * @param string $password
     * @return \Incvisio\Validation
     */
    abstract protected function authenticate($username, $password)
    {
        ...
    }

    /**
     * Get the secret key and other info of a service
     *
     * @param string $serviceId
     * @return array
     */
    abstract protected function getServiceInfo($serviceId)
    {
        ...
    }

    /**
     * Get the information about a user
     *
     * @param string $username
     * @return array|object
     */
    abstract protected function getUserInfo($username)
    {
        ...
    }
}
```

The SSOServer class can be used as controller in an MVC framework.

For more information, checkout the `server` example.

#### Service

When creating a Incvisio\SSO\Service instance, you need to pass the server url, service id and service secret. The service id
and secret needs to be registered at the server (so fetched when using `getServiceInfo($serviceId)`).

**Be careful**: *The service id SHOULD be alphanumeric. In any case it MUST NOT contain the "-" character.*

Next you need to call `attach()`. This will generate a token an redirect the client to the server to attach the token
to the client's session. If the client is already attached, the function will simply return.

When the session is attached you can do actions as login/logout or get the user's info.

```php
$service = new Incvisio\SSO\Service($serverUrl, $serviceId, $serviceSecret);
$service->attach();

$user = $service->getUserInfo();
echo json_encode($user);
```

For more information, checkout the `service` example.