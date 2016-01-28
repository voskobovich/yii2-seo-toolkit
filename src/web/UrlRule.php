<?php

namespace app\seo\web;

use app\seo\models\UrlRoute;
use Yii;
use yii\web\Request;
use yii\web\UrlManager;


/**
 * Class UrlRule
 * @package app\seo\web
 */
class UrlRule extends BaseUrlRule
{
    /**
     * UrlRoute model namespace
     * @var string
     */
    public $modelClass = 'voskobovich\seo\models\UrlRoute';

    /**
     * Duration Cache
     * @var int
     */
    public $cacheDuration = 60;

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

        /** @var UrlRoute $model */
        $model = new $this->modelClass();
        $model = $model::find()
            ->andWhere(['path' => $request->getPathInfo()])
            ->one();

        if ($model == null) {
            return false;
        }

        if ($model->checkAction($model::ACTION_OBJECT_INDEX)) {
            return [$model->getRouteName()];
        } elseif ($model->checkAction($model::ACTION_OBJECT_VIEW)) {
            return [$model->getRouteName(), ['id' => $model->object_id]];
        } elseif ($model->checkAction($model::ACTION_REDIRECT)) {
            $url = $model->url_to;
            if (strpos($url, 'http://') === false) {
                $url = [$url];
            }

            Yii::$app->response->redirect($url, $model->http_code);
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
        if (!parent::createUrl($manager, $route, $params)) {
            return false;
        }

        /** @var UrlRoute $model */
        $model = new $this->modelClass();
        $routeParams = $model::getRouteParamsByName($route);
        if (empty($params)) {
            return false;
        }

        $cacheKey = $this->modelClass . '::createUrl:' . $routeParams['action_key'] . '-' . $routeParams['object_key'];
        $query = $model::find()
            ->select(['action_key', 'object_key', 'path'])
            ->andWhere([
                'action_key' => $routeParams['action_key'],
                'object_key' => $routeParams['object_key'],
            ]);

        if (!empty($params['id'])) {
            $cacheKey .= '-' . $params['id'];
            $query->addSelect(['object_id']);
            $query->andWhere([
                'object_id' => $params['id'],
            ]);
            unset($params['id']);
        }

        if (!$url = Yii::$app->cache->get($cacheKey)) {
            /** @var UrlRoute $model */
            $model = $query->one();

            if ($model == null) {
                return false;
            }

            $url = trim($model->path, '/');
            if (!empty($params) && ($query = http_build_query($params)) !== '') {
                $url .= '?' . $query;
            }

            Yii::$app->cache->set($cacheKey, $url, $this->cacheDuration);
        }

        return $url;
    }
}