<?php

namespace voskobovich\seo\web;

use voskobovich\seo\models\UrlRoute;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Object;
use yii\web\Request;
use yii\web\UrlManager;
use yii\web\UrlRuleInterface;


/**
 * Class UrlRule
 * @package voskobovich\seo\web
 */
class UrlRule extends Object implements UrlRuleInterface
{
    /**
     * UrlRoute model namespace
     * @var string
     */
    public $modelClass;

    /**
     * Paths to skip
     * @var array
     */
    public $skip = [];

    /**
     * Duration Cache
     * @var int
     */
    public $cacheDuration = 60;

    /**
     * @inheritdoc
     *
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (!$this->modelClass) {
            throw new InvalidConfigException('Param "modelClass" can not be empty.');
        }

        if (!is_subclass_of($this->className(), UrlRoute::className())) {
            throw new InvalidConfigException('Object "modelClass" must be implemented ' . UrlRoute::className());
        }

        parent::init();
    }

    /**
     * Parses the given request and returns the corresponding route and parameters.
     * @param UrlManager $manager the URL manager
     * @param Request $request the request component
     * @return array|boolean the parsing result. The route and the parameters are returned as an array.
     * If false, it means this rule cannot be used to parse this path info.
     */
    public function parseRequest($manager, $request)
    {
        foreach ($this->skip as $item) {
            if (strpos($request->getPathInfo(), $item) !== false) {
                return false;
            }
        }

        /** @var UrlRoute $model */
        $model = $this->modelClass;
        $model = $model::find()
            ->andWhere(['path' => $request->getPathInfo()])
            ->one();

        if ($model == null) {
            return false;
        }

        if ($model->checkAction($model::ACTION_INDEX)) {
            return [$model->getRoute()];
        } elseif ($model->checkAction($model::ACTION_VIEW)) {
            return [$model->getRoute(), ['id' => $model->object_id]];
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
        foreach ($this->skip as $item) {
            if (strpos($route, $item) !== false) {
                return false;
            }
        }

        /** @var UrlRoute $model */
        $model = $this->modelClass;
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