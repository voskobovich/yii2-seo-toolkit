<?php

namespace app\seo\web;

use yii\base\Object;
use yii\web\Request;
use yii\web\UrlManager;
use yii\web\UrlRuleInterface;

/**
 * Class CategoryUrlRule
 * @package app\seo\web
 */
abstract class BaseUrlRule extends Object implements UrlRuleInterface
{
    /**
     * Parses the given request and returns the corresponding route and parameters.
     * @param UrlManager $manager the URL manager
     * @param Request $request the request component
     * @return array|boolean the parsing result. The route and the parameters are returned as an array.
     * If false, it means this rule cannot be used to parse this path info.
     */
    public function parseRequest($manager, $request)
    {
        return strpos($request->getPathInfo(), 'backend') === false;
    }

    /**
     * Creates a URL according to the given route and parameters.
     * @param UrlManager $manager the URL manager
     * @param string $route the route. It should not have slashes at the beginning or the end.
     * @param array $params the parameters
     * @return string|boolean the created URL, or false if this rule cannot be used for creating this URL.
     */
    public function createUrl($manager, $route, $params)
    {
        return strpos($route, 'backend') === false;
    }
}