Yii2 SEO Toolkit
===
Помогает реализовать роутинг как в CRM WordPress с управлением из админпанели.

[![License](https://poser.pugx.org/voskobovich/yii2-seo-toolkit/license.svg)](https://packagist.org/packages/voskobovich/yii2-seo-toolkit)
[![Latest Stable Version](https://poser.pugx.org/voskobovich/yii2-seo-toolkit/v/stable.svg)](https://packagist.org/packages/voskobovich/yii2-seo-toolkit)
[![Latest Unstable Version](https://poser.pugx.org/voskobovich/yii2-seo-toolkit/v/unstable.svg)](https://packagist.org/packages/voskobovich/yii2-seo-toolkit)
[![Total Downloads](https://poser.pugx.org/voskobovich/yii2-seo-toolkit/downloads.svg)](https://packagist.org/packages/voskobovich/yii2-seo-toolkit)

Support
---
[GutHub issues](https://github.com/voskobovich/yii2-seo-toolkit/issues).

Usage
---
1. Создать таблицу для хранения роутинга (применить миграцию)
2. Создаем свой экземпляр модели UrlRoute
3. Настроить UrlManager  
  3. Подключить класс UrlManager из пакета 
  3. Подключить классы правил роутинга  
    3. ClearUrlRule для очистки урлов от мусора (двойные слэши, слэш в окончании ...)  
    3. UrlRule отвечает непосредственно за роутинг  
4. В нужной AR модели
  4. Реализовать интерфейс SeoModelInterface
  4. Подключить поведение CreateUrlBehavior
5. Подключаем ActualityUrlBehavior 
6. Создаем CRUD для управления роутами

1. Создаем таблицу (применяем миграцию)
---

```bash
php yii migrate/create create_table__url_route
```
Унаследуйте созданный класс миграции от **\voskobovich\seo\migrations\create_table__url_route**.  
Например:
```php
class <ClassName> extends \voskobovich\seo\migrations\create_table__url_route
{

}
```
Применяем миграцию
```bash
php yii migrate
```

2. Создаем свой экземпляр модели UrlRoute
---
Yii2 SEO toolkit не знает с какими моделями и роутами ему придется работать.  
Чтобы это исправить, нужно унаследоваться от модели UrlRoute из пакета и реализовать 2 метода из UrlRouteInterface.  
Вот пример из моего проекта.
```php
class UrlRoute extends \voskobovich\seo\models\UrlRoute
{
    const OBJECT_CATEGORY = 'category';
    const OBJECT_POST = 'post';
    const OBJECT_TAG = 'tag';
    const OBJECT_USER = 'user';

    /**
     * List objects
     * @return array;
     */
    public static function objectItems()
    {
        return [
            static::OBJECT_CATEGORY => 'Category',
            static::OBJECT_POST => 'Post',
            static::OBJECT_TAG => 'Tag',
            static::OBJECT_USER => 'User',
        ];
    }

    /**
     * Route map for objects
     * @return array;
     */
    public static function routeMap()
    {
        return [
            static::OBJECT_CATEGORY => [
                static::ACTION_INDEX => 'category/index',
                static::ACTION_VIEW => 'category/view',
            ],
            static::OBJECT_POST => [
                static::ACTION_INDEX => 'post/index',
                static::ACTION_VIEW => 'post/view',
            ],
            static::OBJECT_TAG => [
                static::ACTION_INDEX => 'tag/index',
                static::ACTION_VIEW => 'tag/view',
            ],
            static::OBJECT_USER => [
                static::ACTION_INDEX => 'user/index',
                static::ACTION_VIEW => 'user/view',
            ],
        ];
    }
}
```
Из примера видно, что роутинг будет работать с 4-я объектами.  
Для каждого объекта сконфигурирован список стандартных действий "показать все" (index) и "показать один" (view).  
Действий нужны для того, чтобы различать логику обработки роута.  
Например, мы можем сделать вот так:  
1. Роут */foo* это действие *index* для объекта *post*. В итоге, перейдя по */foo* мы получим список всех постов (в yii роутинге это равняется переходу на *post/index*).  
2. Роут */bar* это действие *view* для объекта *post* c *id=3*. В итоге, перейдя по */bar* мы увидим записьь с *id=5* (в yii роутинге это равняется переходу на *post/view*).  
Список действий можно расширить, дополнив список в методе *getActionItems*.

3. Настраивает UrlManager
---
В стандартном в классе UrlManager реализовано кеширование роутов которое помогает ускорить процесс генерации ссылок в стандартном роутинге Yii2. Суть кеширования в том, чтобы запоминать для каких роутов нет правил в конфиге и больше не пытаться построить ссылку для этого роута. То есть, если для роута *post/view* нет правила, то больше для этого роута правил искаться не будет, что и логично. Но для нас это беда, и вот почему.  
Для роута *post/view id=4* может быть правило в таблице маршрутизации, а для *post/view id=5* может не быть.
Если не отключить кеширование, то при генерации ссылок на посты если первой будет сгенерирована ссылка для *post/view id=5* то UrlManager запомнит, что правила для этого роута нет и больше не будет его обрабатывать. В итоге мы не получим наших красивых ссылок для остальных постов.
И так. Подключаем класс UrlManager из пакета.
```php
'urlManager' => [
    'class' => 'voskobovich\seo\web\UrlManager',
    'cacheable' => false,
    'rules' => [
        '' => 'post/index',

        ['class' => '\voskobovich\seo\web\ClearUrlRule'],
        ['class' => '\voskobovich\seo\web\UrlRule', 'modelClass' => 'app\models\UrlRoute'],

        // Default
        '<controller:\w+>/<id:\d+>' => '<controller>/view',
        '<controller:\w+>/<action:[a-zA-Z-]*>/<id:\d+>' => '<controller>/<action>',
        '<controller:\w+>/<action:[a-zA-Z-]*>' => '<controller>/<action>',
    ]
],
```
Правило *ClearUrlRule* умеет:

1. Заменить множество слэшей на один
2. Удалить слэш в конце ссылки
3. Буквы верхнего регистра перевести в нижний

**Внимание!** В целях оптимизации рекомендую настроить эти правила на уровне вашего веб-сервера.

Правило *UrlRule* отвечает за наш волшебный роутинг.  
В атрибут *modelClass* передается модель *UrlRoute* которая была создана на прошлом шаге.

4. Настраивем AR модель
---
Чтобы для новых моделей ссылки автоматом попадили в таблицу роутинга, нужно подключить *CreateUrlBehavior* и реализовать интерфейс *SeoModelInterface*.  
Привожу пример модели *Post* из моего проекта.
```php
class Post extends BaseActiveRecord implements SeoModelInterface
{
    // ...

    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            'createUrlBehavior' => [
                'class' => CreateUrlBehavior::className(),
                'modelClass' => UrlRoute::className(),
                'objectKey' => UrlRoute::OBJECT_POST
            ],
        ];
    }

    /**
     * Build Seo Path
     * @return null|string
     */
    public function getSeoPath()
    {
        /** @var Category|TreeInterface $mainCategory */
        $mainCategory = $this->mainCategory;
        if ($mainCategory) {
            return $mainCategory->path . '/' . $this->slug;
        }

        return null;
    }
    
    // ...
}
```
Метод *getSeoPath()* должен возвращать путь, по которому будет доступна запись поста.  
У меня этот роут состоит из пути к главной категории поста и короткого имени самого поста (/cat/subcat/post-slug).  

Поведению нужно передать нашу модель UrlRoute и сообщить каким объектом является наша AR модель используя ранее созданные константы в модели UrlRoute.

5. Подключаем ActualityUrlBehavior
---
После настройки всего пользователь все еще может перейти по старой ссылке и увидить страницу.  
Например, мы для */post/view?id=6* создали красивый урл */best-post*. Но пользователь все еще может перейти по старой ссылке и получить страницу, хотя в идеале его нужно отправить на новый урл с 301-м редиректом.  
Вот пример обработчика запроса из моего проекта.  
```php
class PostController extends Controller
{
    // ...

    public function actionView($id)
    {
        /** @var Post $model */
        $model = Post::find()
            ->andWhere(['id' => $id])
            ->one();

        $model->attachBehavior('actualityUrlBehavior', [
            'class' => ActualityUrlBehavior::className(),
            'modelClass' => UrlRoute::className(),
            'objectKey' => UrlRoute::OBJECT_POST
        ]);

        return $this->render('view', [
            'model' => $model
        ]);
    }
    
    // ...
}
```
Говорю сразу, подключать поведение на весь контролер не нужно. Это поведение нужно только для view экшена.  
Этому поведению так же нужно передать класс нашей созданной модели UrlRoute и название объекта из константы.

6. Создаем CRUD для управления роутами
---
Во вьюхах есть небольшая логика, так что привожу примеры основных кусков кода из своего проекта.  

Файл **create.php**
```php
<?php $form = ActiveForm::begin() ?>

<?= $form->field($model, 'path') ?>
<?= $form->field($model, 'action_key')->dropDownList($model::getActionItems()) ?>

<button type="submit" >Save</button>

<?php ActiveForm::end() ?>
```

Файл **update.php**
```php
<?php $form = ActiveForm::begin() ?>

<?= $form->field($model, 'path') ?>

<?php if ($model->checkAction($model::ACTION_INDEX)): ?>
    <?= $form->field($model, 'object_key')->dropDownList($model::getObjectItems()) ?>
<?php elseif ($model->checkAction($model::ACTION_VIEW)): ?>
    <?= $form->field($model, 'object_key')->dropDownList($model::getObjectItems()) ?>
    <?= $form->field($model, 'object_id') ?>
<?php elseif ($model->checkAction($model::ACTION_REDIRECT)): ?>
    <?= $form->field($model, 'url_to') ?>
<?php endif; ?>

<?= $form->field($model, 'http_code') ?>

<button type="submit"</button>

<?php ActiveForm::end() ?>
```

Файл **index.php**
```php
<?= GridView::widget([
  'dataProvider' => $dataProvider,
  'filterModel' => $model,
  'columns' => [
      [
          'attribute' => 'path',
          'format' => 'raw',
          'value' => function ($model) {
              /** @var UrlRoute $model */
              return Html::a($model->path, ['update', 'id' => $model->id], ['data-pjax' => 0]);
          }
      ],
      [
          'attribute' => 'object_key',
          'filter' => $model::getObjectItems(),
          'value' => function ($model) {
              /** @var UrlRoute $model */
              return $model->getObject();
          },
          'visible' => array_search($action, [UrlRoute::ACTION_INDEX, UrlRoute::ACTION_VIEW]) !== false
      ],
      [
          'attribute' => 'object_id',
          'visible' => array_search($action, [UrlRoute::ACTION_INDEX, UrlRoute::ACTION_VIEW]) !== false
      ],
      [
          'attribute' => 'url_to',
          'visible' => $action == UrlRoute::ACTION_REDIRECT
      ],
      [
          'attribute' => 'http_code',
          'visible' => $action == UrlRoute::ACTION_REDIRECT
      ],
      [
          'class' => 'voskobovich\grid\advanced\columns\ActionColumn',
          'template' => '{view} {update} {delete}',
          'options' => [
              'width' => '160px'
          ],
          'buttons' => [
              'view' => function ($url, $model, $key) {
                  $options = [
                      'title' => Yii::t('yii', 'View'),
                      'aria-label' => Yii::t('yii', 'View'),
                      'data-pjax' => '0',
                      'class' => 'btn btn-default btn-xs',
                      'target' => '_blank',
                  ];
                  /** @var UrlRoute $model */
                  $url = $model->viewUrl();
                  return Html::a(Yii::t('yii', 'View'), $url, $options);
              }
          ]
      ],
  ],
]) ?>
```

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist voskobovich/yii2-seo-toolkit "^1.0"
```

or add

```
"voskobovich/yii2-seo-toolkit": "^1.0"
```

to the require section of your `composer.json` file.
