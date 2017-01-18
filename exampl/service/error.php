<?php
require_once __DIR__ . '/../../vendor/autoload.php';

$service = new Invisio\SSO\Service(getenv('SSO_SERVER'), getenv('SSO_Service_ID'), getenv('SSO_Service_SECRET'));
$error = $_GET['sso_error'];

?>
<!doctype html>
<html>
    <head>
        <title>Single Sign-On demo (<?= $service->service ?>)</title>
        <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container">
            <h1>Single Sign-On demo <small>(<?= $service->service ?>)</small></h1>

            <div class="alert alert-danger">
                <?= $error ?>
            </div>

            <a href="/">Try again</a>
        </div>
    </body>
</html>
