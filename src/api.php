<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Claroline\Api\Controller;

$locator = new FileLocator(array(__DIR__ . '/config'));
$requestContext = new RequestContext($_SERVER['REQUEST_URI']);
$loader = new YamlFileLoader($locator);
$routes = $loader->load('routes.yml');
$controller = new Controller();
$baseUrl = $requestContext->getBaseUrl();
$path = substr($baseUrl, strpos($baseUrl, "/api.php") + 8);
var_dump($path);
$matcher = new UrlMatcher($routes, $requestContext);
$parameters = $matcher->match($path);
//I'll need to change that to make it less anoying and know the pattern beforehand
$defaults = $parameters['defaults'];
$controller = new Controller();
$callFuncParam = array($controller, $parameters['method']);
unset($parameters['method'], $parameters['_route'], $parameters['defaults']);

foreach ($defaults as $name => $value) {
    $parameters[$name] = $value;
}

return call_user_func_array($callFuncParam, $parameters);
