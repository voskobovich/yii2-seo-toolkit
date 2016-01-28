<?php

namespace app\seo\models;

use yii\db\ActiveRecord;
use Yii;
use yii\helpers\Url;


/**
 * This is the model class for table "{{%url_route}}".
 *
 * @property string $path
 * @property integer $action_key
 * @property integer $object_key
 * @property integer $object_id
 * @property integer $http_code
 * @property string $url_to
 */
class UrlRoute extends ActiveRecord
{
    const OBJECT_CATEGORY = 1;
    const OBJECT_POST = 2;
    const OBJECT_TAG = 3;
    const OBJECT_USER = 4;

    const ACTION_OBJECT_INDEX = 1;
    const ACTION_OBJECT_VIEW = 2;
    const ACTION_REDIRECT = 3;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%url_route}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['path', 'action_key'], 'required'],
            [['action_key', 'object_key', 'object_id'], 'integer'],
            ['http_code', 'integer', 'min' => 100, 'max' => 511],
            [['path', 'url_to'], 'string', 'max' => 255],
            ['path', 'unique'],
            ['path', 'match', 'pattern' => '/^[a-z0-9-_][a-z0-9-_\/]{1,254}[a-z0-9-_]$/'],
            ['object_key', 'in', 'range' => array_keys(static::getObjectItems()), 'skipOnEmpty' => true],
            ['action_key', 'in', 'range' => array_keys(static::getActionItems())],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'path' => Yii::t('vendor/voskobovich/yii2-seo-toolkit/models/urlRoute', 'Path'),
            'action_key' => Yii::t('vendor/voskobovich/yii2-seo-toolkit/models/urlRoute', 'Action'),
            'object_key' => Yii::t('vendor/voskobovich/yii2-seo-toolkit/models/urlRoute', 'Object'),
            'object_id' => Yii::t('vendor/voskobovich/yii2-seo-toolkit/models/urlRoute', 'ID'),
            'http_code' => Yii::t('vendor/voskobovich/yii2-seo-toolkit/models/urlRoute', 'HTTP Code'),
            'url_to' => Yii::t('vendor/voskobovich/yii2-seo-toolkit/models/urlRoute', 'Destination URL'),
        ];
    }

    /**
     * @param null $key
     * @return array
     */
    public static function getObjectItems($key = null)
    {
        $items = [
            static::OBJECT_CATEGORY => Yii::t('vendor/voskobovich/yii2-seo-toolkit/models/urlRoute', 'Category'),
            static::OBJECT_POST => Yii::t('vendor/voskobovich/yii2-seo-toolkit/models/urlRoute', 'Post'),
            static::OBJECT_TAG => Yii::t('vendor/voskobovich/yii2-seo-toolkit/models/urlRoute', 'Tag'),
            static::OBJECT_USER => Yii::t('vendor/voskobovich/yii2-seo-toolkit/models/urlRoute', 'User'),
        ];

        if (!is_null($key)) {
            return isset($items[$key]) ? $items[$key] : null;
        }

        return $items;
    }

    /**
     * @return string
     */
    public function getObject()
    {
        return static::getObjectItems($this->object_key);
    }

    /**
     * @param null $key
     * @return array
     */
    public static function getActionItems($key = null)
    {
        $items = [
            static::ACTION_OBJECT_INDEX => Yii::t('vendor/voskobovich/yii2-seo-toolkit/models/urlRoute', 'Open list'),
            static::ACTION_OBJECT_VIEW => Yii::t('vendor/voskobovich/yii2-seo-toolkit/models/urlRoute', 'Open object'),
            static::ACTION_REDIRECT => Yii::t('vendor/voskobovich/yii2-seo-toolkit/models/urlRoute', 'Redirect'),
        ];

        if (!is_null($key)) {
            return isset($items[$key]) ? $items[$key] : null;
        }

        return $items;
    }

    /**
     * Имя действия в модели
     * @return string
     */
    public function getAction()
    {
        return static::getActionItems($this->action_key);
    }

    /**
     * Проверка действия на равенство значения в модели
     * @param $actions
     * @return string
     */
    public function checkAction($actions)
    {
        if (is_array($actions)) {
            return array_search($this->action_key, $actions) !== false;
        }

        return $this->action_key == $actions;
    }

    /**
     * Маппинг роутов для объекта и действия
     * @param null|integer $objectKey
     * @param null|integer $actionKey
     * @return array|null
     */
    protected static function getObjectRouteItems($objectKey = null, $actionKey = null)
    {
        $items = [
            static::OBJECT_CATEGORY => [
                static::ACTION_OBJECT_INDEX => 'category/index',
                static::ACTION_OBJECT_VIEW => 'category/view',
            ],
            static::OBJECT_POST => [
                static::ACTION_OBJECT_INDEX => 'post/index',
                static::ACTION_OBJECT_VIEW => 'post/view',
            ],
            static::OBJECT_TAG => [
                static::ACTION_OBJECT_INDEX => 'tag/index',
                static::ACTION_OBJECT_VIEW => 'tag/view',
            ],
            static::OBJECT_USER => [
                static::ACTION_OBJECT_INDEX => 'user/index',
                static::ACTION_OBJECT_VIEW => 'user/view',
            ],
        ];

        if (!is_null($objectKey) || !is_null($actionKey)) {
            return isset($items[$objectKey][$actionKey]) ? $items[$objectKey][$actionKey] : null;
        }

        return $items;
    }

    /**
     * Имя роута для объекта и действия
     * @return array|null
     */
    public function getRouteName()
    {
        return static::getObjectRouteItems($this->object_key, $this->action_key);
    }

    /**
     * Поиск объекта и действия по роуту
     * @param $route
     * @return null|array
     */
    public static function getRouteParamsByName($route)
    {
        foreach (static::getObjectRouteItems() as $objectKey => $actions) {
            if (($actionKey = array_search($route, $actions)) !== false) {
                return [
                    'object_key' => $objectKey,
                    'action_key' => $actionKey
                ];
            }
        }

        return null;
    }

    /**
     * @param $objectKey
     * @param $objectId
     * @param $path
     * @return bool
     */
    public static function add($objectKey, $objectId, $path)
    {
        $model = static::find()
            ->andWhere([
                'object_key' => $objectKey,
                'object_id' => $objectId
            ])
            ->select(['object_key', 'object_id'])
            ->count();

        if ($model) {
            return true;
        }

        $model = new static();
        $model->setAttributes([
            'path' => ltrim($path, '/'),
            'action_key' => static::ACTION_OBJECT_VIEW,
            'object_key' => $objectKey,
            'object_id' => $objectId,
        ]);
        return $model->save();
    }

    /**
     * @param bool $isAbsolute
     * @return string
     */
    public function viewUrl($isAbsolute = false)
    {
        return Url::toRoute('/' . $this->path, $isAbsolute);
    }
}