<?php

namespace app\seo\web;

use Yii;
use yii\web\Request;
use yii\web\UrlManager;


/**
 * Class ClearUrlRule
 * @package app\seo\web
 */
class ClearUrlRule extends BaseUrlRule
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
        if (!parent::parseRequest($manager, $request)) {
            return false;
        }

        $path = $request->getPathInfo();
        $redirect = false;

        // Слэш в конце
        if (substr($path, -1) == '/') {
            $redirect = true;
            $path = trim($path, '/');
        }

        // Двойной слэш
        if (strpos($path, '//') !== false) {
            $redirect = true;
            $path = str_replace('//', '/', $path);
        }

        // Символы в верхнем регистре
        if (($tmpUrl = strtolower($path)) !== $path) {
            $redirect = true;
            $path = $tmpUrl;
        }

        if ($redirect) {
            Yii::$app->response->redirect([$path], 301);
            Yii::$app->end();
        }

        return false;
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
        return false;
    }
}