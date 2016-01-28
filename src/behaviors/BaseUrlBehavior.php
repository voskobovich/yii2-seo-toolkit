<?php

namespace voskobovich\seo\behaviors;

use voskobovich\seo\interfaces\SeoModelInterface;
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
            throw new InvalidConfigException('Owner must be implemented "app\seo\interfaces\SeoUrlInterface"');
        }
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->objectKey == null) {
            throw new InvalidConfigException('Param "actionKey" must be contain object key.');
        }
    }
}