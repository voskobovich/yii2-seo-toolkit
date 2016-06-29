<?php

namespace voskobovich\seo\models;

use voskobovich\seo\interfaces\UrlRouteInterface;
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
abstract class UrlRoute extends ActiveRecord implements UrlRouteInterface
{
    const ACTION_INDEX = 'index';
    const ACTION_VIEW = 'view';
    const ACTION_REDIRECT = 'redirect';

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
            [['object_id'], 'integer'],
            [['action_key', 'object_key'], 'string', 'max' => 30],
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
        $items = static::objectItems();

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
     * @param null|integer $objectKey
     * @param null|integer $actionKey
     * @return array|null
     */
    protected static function getRouteMap($objectKey = null, $actionKey = null)
    {
        $items = static::routeMap();

        if (!is_null($objectKey) || !is_null($actionKey)) {
            return isset($items[$objectKey][$actionKey]) ? $items[$objectKey][$actionKey] : null;
        }

        return $items;
    }

    /**
     * @return array|null
     */
    public function getRoute()
    {
        return static::getRouteMap($this->object_key, $this->action_key);
    }

    /**
     * @param $route
     * @return null|array
     */
    public static function getRouteParamsByName($route)
    {
        foreach (static::getRouteMap() as $objectKey => $actions) {
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
     * @param null $key
     * @return array
     */
    public static function getActionItems($key = null)
    {
        $items = [
            static::ACTION_INDEX => Yii::t('vendor/voskobovich/yii2-seo-toolkit/models/urlRoute', 'Open list'),
            static::ACTION_VIEW => Yii::t('vendor/voskobovich/yii2-seo-toolkit/models/urlRoute', 'Open object'),
            static::ACTION_REDIRECT => Yii::t('vendor/voskobovich/yii2-seo-toolkit/models/urlRoute', 'Redirect'),
        ];

        if (!is_null($key)) {
            return isset($items[$key]) ? $items[$key] : null;
        }

        return $items;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return static::getActionItems($this->action_key);
    }

    /**
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
     * @param $objectKey
     * @param $objectId
     * @param $path
     * @return bool
     */
    public static function add($objectKey, $objectId, $path)
    {
        /** @var static $model */
        $model = static::find()
            ->andWhere([
                'object_key' => $objectKey,
                'object_id' => $objectId
            ])
            ->one();

        $path = ltrim($path, '/');

        if ($model) {
            if ($model->path == $path) {
                return true;
            }

            $model->setAttribute('path', $path);
        } else {
            $model = new static();
            $model->setAttributes([
                'path' => $path,
                'action_key' => static::ACTION_VIEW,
                'object_key' => $objectKey,
                'object_id' => $objectId,
            ]);
        }

        return $model->save();
    }

    /**
     * @param $objectKey
     * @param $objectId
     * @return int
     */
    public static function remove($objectKey, $objectId)
    {
        /** @var static $model */
        $model = static::find()
            ->andWhere([
                'object_key' => $objectKey,
                'object_id' => $objectId
            ])
            ->select(['id'])
            ->one();

        if (!$model) {
            return true;
        }

        return $model->delete() > 0;
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