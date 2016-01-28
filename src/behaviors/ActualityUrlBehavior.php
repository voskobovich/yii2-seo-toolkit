<?php

namespace app\seo\behaviors;

use app\seo\models\UrlRoute;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;


/**
 * Class ActualityUrlBehavior
 * @package app\seo\behaviors
 */
class ActualityUrlBehavior extends BaseUrlBehavior
{
    /**
     * Redirect HTTP Code
     * @var int
     */
    public $httpCode = 301;

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_FIND => 'run',
        ];
    }

    /**
     * @throws InvalidConfigException
     * @throws \yii\base\ExitException
     */
    public function run()
    {
        $request = Yii::$app->request;

        if (strpos($request->getPathInfo(), 'backend') !== false) {
            return;
        }

        /** @var ActiveRecord $model */
        $model = $this->owner;

        /** @var UrlRoute $urlRoute */
        $urlRoute = UrlRoute::find()
            ->select(['action_key', 'object_key', 'object_id', 'path'])
            ->andWhere([
                'action_key' => UrlRoute::ACTION_OBJECT_VIEW,
                'object_key' => $this->objectKey,
                'object_id' => $model->getPrimaryKey(),
            ])
            ->one();

        if (!$urlRoute) {
            return;
        }

        if ($urlRoute->path !== $request->getPathInfo()) {
            Yii::$app->getResponse()->redirect([$urlRoute->path], $this->httpCode);
            Yii::$app->end();
        }
    }
}