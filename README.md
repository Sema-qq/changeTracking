# changeTracking
Расширение с необходимым набором классов для фиксации
 изменения атрибутов у моделей.
 
 Завязано на ActiveRecord, либо нужно иметь:
 * метод `getAttributeLabel`;
 * свойство `primaryKey`.
 
 Ну и в переопределении руки не связывал..
 
 ## ChangeBehavior
 Поведение (с) кэп.
 
 Подписывается на события:
 * afterInsert - фиксирует атрибуты при вставке;
 * beforeUpdate - сохраняет измененные атрибуты;
 * afterUpdate - фиксирует изменения при редактировании.

### Атрибуты
* `public $attributes` - массив атрибутов за которыми будем следить
 (обязательный);
* `public $component` - объект `ChangeComponent` (обязательный);
* `public $events` - массив событий, 
на которые мы будем подписываться (не обязательный);
* `protected $oldAttributes` - массив со старыми атрибутами.
 
### Пример подключения

```$xslt
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'change' => [
                'class' => ChangeBehavior::class,
                'attributes' => [
                    'STATE',
                    'PRIORITY',
                    'OWNER_USER_ID',
                    'FEATURE_INT_ID',
                    'EXEC_USER_ID',
                    'COMMENTS'
                ],
                'component' => Yii::$app->change
            ]
        ];
    }

```

## ChangedAttribute
Вспомогательный класс, чтобы работать с объектами, а не с массивами.

### Атрибуты
* `public $label` - название атрибута;
* `public $new` - новое значение атрибута;
* `public $old` - старое значение атрибута.

## ChangeComponent
Компонент, собирает в себе массив всех изменений и отдает их в обработчики.

### Методы
#### init
Подписывается на завершение работы приложения.
#### addObject
Сохраняет изменения в массив. 

Учитывается то, что один и тот же атрибут может измениться несколько раз.

Так же учтена возможность множественного сохранения, т.е. не одного объекта.

##### Параметры
* `object` - объект, у которого фиксируем изменения;
* `attributes` - массив объектов `ChangedAttribute`.

### run
Запускает в работы обработчики.

### Атрибуты
* `public $handlers` - массив классов обработчиков, 
ожидаются реализующие интерфейс `changeTracking\Handler`;
* `public $objects` - массив изменений вида:
```$xslt
      'common\models\mc\Ticket-120007923' =>
           'object' => object Ticket,
           'attributes =>
               'FEATURE_INT_ID' =>
                   object(changeTraking\ChangedAttribute)
                       public 'label' => 'Категория'
                       public 'new' => 5898
                       public 'old' => 5782
               'PRIORITY' =>
                   object(changeTraking\ChangedAttribute)
                       public 'label' => 'Приоритет'
                       public 'new' => 2
                       public 'old' => 1
      'common\models\mc\Comment-36474459' =>
           'object' => object Comment,
           'attributes =>
               'COMMENTS' =>
                   object(changeTraking\ChangedAttribute)
                       public 'label' => 'Комментарий для сотрудников'
                       public 'new' => 'Васька! Шоб окно сегодня у клиента сменил!'
                       public 'old' => null
```

### Подключение в конфиге

```$xslt
    # common/config/main.php | protected/config/main.php
    'components' => [
        'change' => [
            'class' => changeTracking\ChangeComponent::class,
            'handlers' => [
                \common\changesNotify\EmailSender::class
                \common\changesNotify\PushSender::class
            ]
        ]
    ],
];

```

## Handler
Интерфейс для обработиков.

Один метод `run()`, в котором и запускается логика заложенная в обработчик.