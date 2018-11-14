<?php

namespace Sema\ChangeTracking;

use yii\db\ActiveRecord;
use yii\base\Behavior;
use yii\base\Event;
use Yii;

/**
 * Поведение, фиксирующее изменения
 */
class ChangeBehavior extends Behavior
{
    /** @var array Массив атрибутов с которыми работаем */
    public $attributes = [];
    /** @var ChangeComponent Имя компонента */
    public $component;
    /** @var array Массив евентов на которые нужно подписаться */
    public $events = [];
    /** @var array Массив старых атрибутов */
    protected $oldAttributes = [];

    /** @inheritdoc */
    public function events()
    {
        return $this->events ?: [
            ActiveRecord::EVENT_AFTER_INSERT    => 'insert',
            ActiveRecord::EVENT_AFTER_UPDATE    => 'update',
            ActiveRecord::EVENT_BEFORE_UPDATE   => 'setOldAttributes',
        ];
    }

    /**
     * @param Event $event
     */
    public function insert($event)
    {
        $result = [];
        /** @var ActiveRecord $sender */
        $sender = $event->sender;
        $sender->isNewRecord = $event->name == ActiveRecord::EVENT_AFTER_INSERT;
        foreach ($this->attributes as $attribute) {
            if (isset($sender->attributes[$attribute])) {
                $result[$attribute] = new ChangedAttribute([
                    'label' => $sender->getAttributeLabel($attribute),
                    'new' => $sender->attributes[$attribute],
                ]);
            }
        }

        if (!empty($result)) {
            $this->component->addObject($sender, $result);
        }
    }

    /**
     * @param Event $event
     */
    public function update($event)
    {
        $result = [];
        /** @var ActiveRecord $sender */
        $sender = $event->sender;
        foreach ($this->attributes as $attribute) {
            if (isset($sender->attributes[$attribute]) &&
                $sender->attributes[$attribute] != $this->oldAttributes[$attribute]
            ) {
                $result[$attribute] = new ChangedAttribute([
                    'label' => $sender->getAttributeLabel($attribute),
                    'new' => $sender->attributes[$attribute],
                    'old' => $this->oldAttributes[$attribute]
                ]);
            }
        }

        if (!empty($result)) {
            $this->component->addObject($sender, $result);
        }
    }

    /**
     * Запоминает старые атрибуты
     * @param $event
     */
    public function setOldAttributes($event)
    {
        $this->oldAttributes = $event->sender->oldAttributes;
    }
}
