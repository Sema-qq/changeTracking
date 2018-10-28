<?php

namespace changeTracking;

use yii\db\ActiveRecord;
use yii\web\Application;
use yii\base\Component;

/**
 * Компонент для передачи данных в обработчики
 */
class ChangeComponent extends Component
{
    /** @var array Массив имен классов обработчиков */
    public $handlers = [];
    /**
     * @var array Массив
     * пример:
     * 'common\models\mc\Ticket-120007923' =>
     *      'object' => object Ticket,
     *      'attributes =>
     *          'FEATURE_INT_ID' =>
     *              object(changeTraking\ChangedAttribute)
     *                  public 'label' => 'Категория'
     *                  public 'new' => 5898
     *                  public 'old' => 5782
     *          'PRIORITY' =>
     *              object(changeTraking\ChangedAttribute)
     *                  public 'label' => 'Приоритет'
     *                  public 'new' => 2
     *                  public 'old' => 1
     * 'common\models\mc\Comment-36474459' =>
     *      'object' => object Comment,
     *      'attributes =>
     *          'COMMENTS' =>
     *              object(changeTraking\ChangedAttribute)
     *                  public 'label' => 'Комментарий для сотрудников'
     *                  public 'new' => 'Васька! Шоб окно сегодня у клиента сменил!'
     *                  public 'old' => null
     */
    private $objects = [];

    /**
     * @inheritdoc
     */
    public function init()
    {
        \Yii::$app->on(Application::EVENT_AFTER_REQUEST, [$this, 'run']);
        parent::init();
    }

    /**
     * Заполняет массив $this->objects
     * @param ActiveRecord $object
     * @param ChangedAttribute[] $attributes
     */
    public function addObject($object, $attributes)
    {
        $key = get_class($object) . "-{$object->primaryKey}";
        if (isset($this->objects[$key])) {
            $this->objects[$key]['object'] = $object;
            /**
             * Сделаем замены атрибутов, чтобы не получить одни и те же изменения несколько раз,
             * в случае, если вернулось старое значение,то уведомлять о нем нет необходимости
             * @var  string $name Название атрибута
             * @var ChangedAttribute $obj
             */
            foreach ($attributes as $name => $obj) {
                $oldObject = isset($this->objects[$key]['attributes'][$name]) ?
                    $this->objects[$key]['attributes'][$name] : null;
                if ($oldObject) {
                    /** @var ChangedAttribute $oldObject */
                    if ($oldObject->new != $obj->new) {
                        $oldObject->new = $obj->new;
                        if ($oldObject->old == $oldObject->new) {
                            unset($this->objects[$key]['attributes'][$name]);
                        }
                    }
                } else {
                    $this->objects[$key] = [
                        'attributes' => [
                            $name => $obj
                        ]
                    ];
                }
            }

            if (empty($this->objects[$key]['attributes'])) {
                unset($this->objects[$key]);
            }
        } else {
            $this->objects[$key] = [
                'object' => $object,
                'attributes' => $attributes
            ];
        }
    }

    /**
     * Запускает обработчики в работу
     */
    final public function run()
    {
        if (!empty($this->objects)) {
            /** @var string $className */
            foreach ($this->handlers as $className) {
                $handler = new $className;
                if ($handler instanceof Handler) {
                    $handler->run($this->objects);
                }
            }
        }
    }
}
