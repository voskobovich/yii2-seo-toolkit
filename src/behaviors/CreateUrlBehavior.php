<?php

namespace voskobovich\seo\behaviors;

use voskobovich\seo\models\UrlRoute;
use voskobovich\seo\interfaces\SeoModelInterface;
use Yii;
use yii\db\ActiveRecord;


/**
 * Class CreateUrlBehavior
 * @package voskobovich\seo\behaviors
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
        /** @var ActiveRecord|SeoModelInterface $model */
        $model = $this->owner;

        if ($path = $model->getSeoPath()) {
            /** @var UrlRoute $urlRoute */
            $urlRoute = $this->modelClass;
            $urlRoute::add($this->objectKey, $model->getPrimaryKey(), $path);
        }
    }
}