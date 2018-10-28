<?php

namespace changeTracking;

/**
 * Интерфейс для обработчиков
 */
interface Handler
{
    /**
     * Метод запускающий обработку изменений
     * @param array $array
     * @return mixed
     */
    public function run($array);
}