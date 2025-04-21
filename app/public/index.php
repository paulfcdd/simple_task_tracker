<?php // public/index.php

declare(strict_types=1);

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ContainerControllerResolver;
use Symfony\Component\HttpKernel\EventListener\ErrorListener;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Routing\Loader\YamlFileLoader as RoutingYamlFileLoader;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

require_once dirname(__DIR__).'/vendor/autoload.php';

$debug = true;
$projectDir = dirname(__DIR__);

$containerBuilder = new ContainerBuilder();
$loader = new YamlFileLoader($containerBuilder, new FileLocator($projectDir.'/config'));
$loader->load('services.yaml');
$containerBuilder->compile();

$request = Request::createFromGlobals();
$requestStack = new RequestStack();
$requestStack->push($request);

$routeFileLocator = new FileLocator($projectDir.'/config');
$routeLoader = new RoutingYamlFileLoader($routeFileLocator);
$routes = $routeLoader->load('routes.yaml');

$context = new RequestContext();
$context->fromRequest($request);
$matcher = new UrlMatcher($routes, $context);

$dispatcher = new EventDispatcher();
$logger = null;

$routerListener = new RouterListener($matcher, $requestStack, $context, $logger, $projectDir, $debug);
$dispatcher->addSubscriber($routerListener);

// Optional: Add ErrorListener for more robust error handling
// $errorListener = new ErrorListener('App\\Controller\\ErrorController::show', $logger, $debug);
// $dispatcher->addSubscriber($errorListener);

$controllerResolver = new ContainerControllerResolver($containerBuilder, $logger);

$argumentMetadataFactory = null;
$argumentValueResolvers = ArgumentResolver::getDefaultArgumentValueResolvers();
$argumentResolver = new ArgumentResolver($argumentMetadataFactory, $argumentValueResolvers);

$kernel = new HttpKernel($dispatcher, $controllerResolver, $requestStack, $argumentResolver);

try {
    $response = $kernel->handle($request);
    $response->send();
    $kernel->terminate($request, $response);
} catch (\Throwable $e) {
    if ($debug) {
        error_log($e->getMessage() . "\n" . $e->getTraceAsString());
        $message = 'An unexpected error occurred: ' . $e->getMessage();
    } else {
        error_log('Critical Error: ' . $e->getMessage());
        $message = 'An internal server error occurred.';
    }

    if (!headers_sent()) {
        $statusCode = $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $e->getStatusCode() : 500;
        $response = new JsonResponse(['error' => $message], $statusCode);
        $response->send();
    } else {
        error_log("Headers already sent, could not send error response for: " . $e->getMessage());
    }
}
