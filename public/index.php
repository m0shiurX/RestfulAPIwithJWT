<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Firebase\JWT\JWT;
require '../vendor/autoload.php';
require '../src/config/db.php';

$config['displayErrorDetails'] = true;
$config['addContentLengthHeader'] = false;
//$config['determineRouteBeforeAppMiddleware'] = true;


$app = new \Slim\App(["setings" => $config]);


//Customer Routes
require '../src/routes/customers.php';

	// Default Route
	//  $app->get('/{name}', function (Request $request, Response $response) {
	//     $name = $request->getAttribute('name');
	//     $response->getBody()->write("Hello, $name");
	//     return $response;
	//  });
	// $app->get('/', function (Request $request, Response $response) {
	//    //$name = $request->getAttribute('name');
	//    $response->getBody()->write("Your Slim App is running ! !");

	//    return $response;
	// });

$app->run();