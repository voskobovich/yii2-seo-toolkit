<?php

namespace app\seo\behaviors;

use app\seo\models\UrlRoute;
use app\seo\interfaces\SeoUrlInterface;
use Yii;
use yii\db\ActiveRecord;


/**
 * Class CreateUrlBehavior
 * @package app\seo\behaviors
 */
class CreateUrlBehavior extends BaseUrlBehavior
{
    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_UPDATE => 'run',
        ];
    }

    /**
     * Создание ур
     */
    public function run()
    {
        /** @var ActiveRecord|SeoUrlInterface $model */
        $model = $this->owner;

        if ($path = $model->getSeoPath()) {
            UrlRoute::add($this->objectKey, $model->getPrimaryKey(), $path);
        }
    }
}