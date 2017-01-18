<?php
use Incvisio\SSO\NotAttachedException;
use Incvisio\SSO\Exception as SsoException;

require_once __DIR__ . '/../../vendor/autoload.php';

if (isset($_GET['sso_error'])) {
    header("Location: error.php?sso_error=" . $_GET['sso_error'], true, 307);
    exit;
}

$service = new Invisio\SSO\Service(getenv('SSO_SERVER'), getenv('SSO_Service_ID'), getenv('SSO_Service_SECRET'));
$service->attach(true);

try {
    $user = $service->getUserInfo();
} catch (NotAttachedException $e) {
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
} catch (SsoException $e) {
    header("Location: error.php?sso_error=" . $e->getMessage(), true, 307);
}

if (!$user) {
    header("Location: login.php", true, 307);
    exit;
}
?>
<!doctype html>
<html>
    <head>
        <title><?= $service->service ?> (Single Sign-On demo)</title>
        <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container">
            <h1><?= $service->service ?> <small>(Single Sign-On demo)</small></h1>
            <h3>Logged in</h3>

            <pre><?= json_encode($user, JSON_PRETTY_PRINT); ?></pre>

            <a id="logout" class="btn btn-default" href="login.php?logout=1">Logout</a>
        </div>
    </body>
</html>
