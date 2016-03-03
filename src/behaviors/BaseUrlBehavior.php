<?php

namespace voskobovich\seo\behaviors;

use voskobovich\seo\interfaces\SeoModelInterface;
use voskobovich\seo\models\UrlRoute;
use Yii;
use yii\base\Behavior;
use yii\base\InvalidConfigException;


/**
 * Class BaseUrlBehavior
 * @package voskobovich\seo\behaviors
 */
abstract class BaseUrlBehavior extends Behavior
{
    /**
     * UrlRoute model namespace
     * @var string
     */
    public $modelClass;

    /**
     * UrlRoute object key
     * @var int
     */
    public $objectKey;

    /**
     * @param \yii\base\Component $owner
     * @throws InvalidConfigException
     */
    public function attach($owner)
    {
        parent::attach($owner);

        if ($owner && !$owner instanceof SeoModelInterface) {
            throw new InvalidConfigException('Owner must be implemented "app\seo\interfaces\SeoModelInterface"');
        }
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->objectKey == null) {
            throw new InvalidConfigException('Param "actionKey" must be contain object key.');
        }

        if (!$this->modelClass) {
            throw new InvalidConfigException('Param "modelClass" can not be empty.');
        }

        if (!is_subclass_of($this->modelClass, UrlRoute::className())) {
            throw new InvalidConfigException('Object "modelClass" must be implemented ' . UrlRoute::className());
        }

        parent::init();
    }
}