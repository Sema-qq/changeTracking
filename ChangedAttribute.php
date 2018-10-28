<?php

namespace changeTracking;

use yii\base\BaseObject;

/**
 * Для удобства использования измененных атрибутов
 */
class ChangedAttribute extends BaseObject
{
    /** @var string Имя атрибута */
    public $label;
    /** @var mixed Новое значение */
    public $new;
    /** @var mixed Старое значение */
    public $old;
}
