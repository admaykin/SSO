<?php
namespace Incvisio\SSO;

use Desarrolla2\Cache\Cache;
use Desarrolla2\Cache\Adapter;

/**
 * Single sign-on server.
 *
 * The SSO server is responsible of managing users sessions which are available for services.
 *
 * To use the SSO server, extend this class and implement the abstract methods.
 * This class may be used as controller in an MVC application.
 */
abstract class Server
{
    /**
     * @var array
     */
    protected $options = ['files_cache_directory' => '/tmp', 'files_cache_ttl' => 36000];

    /**
     * Cache that stores the special session data for the services.
     *
     * @var Cache
     */
    protected $cache;

    /**
     * @var string
     */
    protected $returnType;

    /**
     * @var mixed
     */
    protected $serviceId;


    /**
     * Class constructor
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options + $this->options;
        $this->cache = $this->createCacheAdapter();
    }

    /**
     * Create a cache to store the service session id.
     *
     * @return Cache
     */
    protected function createCacheAdapter()
    {
        $adapter = new Adapter\File($this->options['files_cache_directory']);
        $adapter->setOption('ttl', $this->options['files_cache_ttl']);

        return new Cache($adapter);
    }

    /**
     * Start the session for service requests to the SSO server
     */
    public function startServiceSession()
    {
        if (isset($this->serviceId)) return;

        $sid =  $this->getServiceSessionID();

        if ($sid == false) {
            return $this->fail("Service didn't send a session key", 400);
        }

        $linkedId = $this->cache->get($sid);

        if (!$linkedId) {
            return $this->fail("The service session id isn't attached to a user session", 403);
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            if ($linkedId !== session_id()) throw new \Exception("Session has already started", 400);
            return;
        }

        session_id($linkedId);
        session_start();

        $this->serviceId = $this->validateServiceSessionId($sid);
    }

        /**
         * Get session ID from header Authorization or from $_GET/$_POST
         */
        protected function getServiceSessionID()
        {
            $headers = getallheaders();

            if (isset($headers['Authorization']) &&  strpos($headers['Authorization'], 'Bearer') === 0) {
                $headers['Authorization'] = substr($headers['Authorization'], 7);
                return $headers['Authorization'];
            }
            if (isset($_GET['access_token'])) {
                return $_GET['access_token'];
            }
            if (isset($_POST['access_token'])) {
                return $_POST['access_token'];
            }
            if (isset($_GET['sso_session'])) {
                return $_GET['sso_session'];
            }

            return false;
        }

    /**
     * Validate the service session id
     *
     * @param string $sid session id
     * @return string  the service id
     */
    protected function validateServiceSessionId($sid)
    {
        $matches = null;

        if (!preg_match('/^SSO-(\w*+)-(\w*+)-([a-z0-9]*+)$/', $this->getServiceSessionID(), $matches)) {
            return $this->fail("Invalid session id");
        }

        $serviceId = $matches[1];
        $token = $matches[2];

        if ($this->generateSessionId($serviceId, $token) != $sid) {
            return $this->fail("Checksum failed: Client IP address may have changed", 403);
        }

        return $serviceId;
    }

    /**
     * Start the session when a user visits the SSO server
     */
    protected function startUserSession()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    }

    /**
     * Generate session id from session token
     *
     * @param string $serviceId
     * @param string $token
     * @return string
     */
    protected function generateSessionId($serviceId, $token)
    {
        $service = $this->getServiceInfo($serviceId);

        if (!isset($service)) return null;

        return "SSO-{$serviceId}-{$token}-" . hash('sha256', 'session' . $token . $service['secret']);
    }

    /**
     * Generate session id from session token
     *
     * @param string $serviceId
     * @param string $token
     * @return string
     */
    protected function generateAttachChecksum($serviceId, $token)
    {
        $service = $this->getServiceInfo($serviceId);

        if (!isset($service)) return null;

        return hash('sha256', 'attach' . $token . $service['secret']);
    }


    /**
     * Detect the type for the HTTP response.
     * Should only be done for an `attach` request.
     */
    protected function detectReturnType()
    {
        if (!empty($_GET['return_url'])) {
            $this->returnType = 'redirect';
        } elseif (!empty($_GET['callback'])) {
            $this->returnType = 'jsonp';
        } elseif (strpos($_SERVER['HTTP_ACCEPT'], 'image/') !== false) {
            $this->returnType = 'image';
        } elseif (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            $this->returnType = 'json';
        }
    }

    /**
     * Attach a user session to a service session
     */
    public function attach()
    {
        $this->detectReturnType();

        if (empty($_REQUEST['service'])) return $this->fail("No service specified", 400);
        if (empty($_REQUEST['token'])) return $this->fail("No token specified", 400);

        if (!$this->returnType) return $this->fail("No return url specified", 400);

        $checksum = $this->generateAttachChecksum($_REQUEST['service'], $_REQUEST['token']);

        if (empty($_REQUEST['checksum']) || $checksum != $_REQUEST['checksum']) {
            return $this->fail("Invalid checksum", 400);
        }

        $this->startUserSession();
        $sid = $this->generateSessionId($_REQUEST['service'], $_REQUEST['token']);

        $this->cache->set($sid, $this->getSessionData('id'));
        $this->outputAttachSuccess();
    }

    /**
     * Output on a successful attach
     */
    protected function outputAttachSuccess()
    {
        if ($this->returnType === 'image') {
            $this->outputImage();
        }

        if ($this->returnType === 'json') {
            header('Content-type: application/json; charset=UTF-8');
            echo json_encode(['success' => 'attached']);
        }

        if ($this->returnType === 'jsonp') {
            $data = json_encode(['success' => 'attached']);
            echo $_REQUEST['callback'] . "($data, 200);";
        }

        if ($this->returnType === 'redirect') {
            $url = $_REQUEST['return_url'];
            header("Location: $url", true, 307);
            echo "You're being redirected to <a href='{$url}'>$url</a>";
        }
    }

    /**
     * Output a 1x1px transparent image
     */
    protected function outputImage()
    {
        header('Content-Type: image/png');
        echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQ'
            . 'MAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZg'
            . 'AAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=');
    }


    /**
     * Authenticate
     */
    public function login()
    {
        $this->startServiceSession();

        if (empty($_POST['username'])) $this->fail("No username specified", 400);
        if (empty($_POST['password'])) $this->fail("No password specified", 400);

        $validation = $this->authenticate($_POST['username'], $_POST['password']);

        if ($validation->failed()) {
            return $this->fail($validation->getError(), 400);
        }

        $this->setSessionData('sso_user', $_POST['username']);
        $this->userInfo();
    }

    /**
     * Log out
     */
    public function logout()
    {
        $this->startServiceSession();
        $this->setSessionData('sso_user', null);

        header('Content-type: application/json; charset=UTF-8');
        http_response_code(204);
    }

    /**
     * Ouput user information as json.
     */
    public function userInfo()
    {
        $this->startServiceSession();
        $user = null;

        $username = $this->getSessionData('sso_user');

        if ($username) {
            $user = $this->getUserInfo($username);
            if (!$user) return $this->fail("User not found", 500); // Shouldn't happen
        }

        header('Content-type: application/json; charset=UTF-8');
        echo json_encode($user);
    }


    /**
     * Set session data
     *
     * @param string $key
     * @param string $value
     */
    protected function setSessionData($key, $value)
    {
        if (!isset($value)) {
            unset($_SESSION[$key]);
            return;
        }

        $_SESSION[$key] = $value;
    }

    /**
     * Get session data
     *
     * @param string $key
     * @return mixed
     */
    protected function getSessionData($key)
    {
        if ($key === 'id') return session_id();

        return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
    }


    /**
     * An error occured.
     *
     */
    protected function fail($message, $http_status = 500)
    {
        if (!empty($this->options['fail_exception'])) {
            throw new Exception($message, $http_status);
        }

        if ($http_status === 500) trigger_error($message, E_USER_WARNING);

        if ($this->returnType === 'jsonp') {
            echo $_REQUEST['callback'] . "(" . json_encode(['error' => $message]) . ", $http_status);";
            exit();
        }

        if ($this->returnType === 'redirect') {
            $url = $_REQUEST['return_url'] . '?sso_error=' . $message;
            header("Location: $url", true, 307);
            echo "You're being redirected to <a href='{$url}'>$url</a>";
            exit();
        }

        http_response_code($http_status);
        header('Content-type: application/json; charset=UTF-8');

        echo json_encode(['error' => $message]);
        exit();
    }


    /**
     * Authenticate using user credentials
     *
     * @param string $username
     * @param string $password
     * @return \Incvisio\Validation
     */
    abstract protected function authenticate($username, $password);

    /**
     * Get the secret key and other info of a service
     *
     * @param string $serviceId
     * @return array
     */
    abstract protected function getServiceInfo($serviceId);

    /**
     * Get the information about a user
     *
     * @param string $username
     * @return array|object
     */
    abstract protected function getUserInfo($username);
}
