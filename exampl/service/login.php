<?php
use Incvisio\SSO\NotAttachedException;
require_once __DIR__ . '/../../vendor/autoload.php';

$service = new Invisio\SSO\Service(getenv('SSO_SERVER'), getenv('SSO_Service_ID'), getenv('SSO_Service_SECRET'));
$service->attach(true);

try {
    if (!empty($_GET['logout'])) {
        $service->logout();
    } elseif ($service->getUserInfo() || ($_SERVER['REQUEST_METHOD'] == 'POST' && $service->login($_POST['username'], $_POST['password']))) {
        header("Location: index.php", true, 303);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') $errmsg = "Login failed";
} catch (NotAttachedException $e) {
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
} catch (Incvisio\SSO\Exception $e) {
    $errmsg = $e->getMessage();
}

?>
<!doctype html>
<html>
    <head>
        <title><?= $service->service ?> | Login (Single Sign-On demo)</title>
        <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css" rel="stylesheet">

        <style>
            h1 {
                margin-bottom: 30px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1><?= $service->service ?> <small>(Single Sign-On demo)</small></h1>

            <?php if (isset($errmsg)): ?><div class="alert alert-danger"><?= $errmsg ?></div><?php endif; ?>

            <form class="form-horizontal" action="login.php" method="post">
                <div class="form-group">
                    <label for="inputUsername" class="col-sm-2 control-label">Username</label>
                    <div class="col-sm-10">
                        <input type="text" name="username" class="form-control" id="inputUsername">
                    </div>
                </div>
                <div class="form-group">
                    <label for="inputPassword" class="col-sm-2 control-label">Password</label>
                    <div class="col-sm-10">
                        <input type="password" name="password" class="form-control" id="inputPassword">
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10">
                        <button type="submit" class="btn btn-default">Login</button>
                    </div>
                </div>
            </form>
        </div>
    </body>
</html>
