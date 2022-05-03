<?php
/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\DuplicateFinder\Controller\PageController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */
return [
    'routes' => [
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        ['name' => 'duplicate_api#list', 'url' => '/api/{apiVersion}/Duplicates', 'verb' => 'GET', 'requirements' => ['apiVersion' => 'v1']],
        ['name' => 'settings_api#list', 'url' => '/api/{apiVersion}/Settings', 'verb' => 'GET', 'requirements' => ['apiVersion' => 'v1']],
        ['name' => 'settings_api#save', 'url' => '/api/{apiVersion}/Settings', 'verb' => 'PATCH', 'requirements' => ['apiVersion' => 'v1']],
        ['name' => 'filter_api#list', 'url' => '/api/{apiVersion}/Filters', 'verb' => 'GET', 'requirements' => ['apiVersion' => 'v1']],
        ['name' => 'filter_api#save', 'url' => '/api/{apiVersion}/Filters', 'verb' => 'PUT', 'requirements' => ['apiVersion' => 'v1']],
    ]
];
