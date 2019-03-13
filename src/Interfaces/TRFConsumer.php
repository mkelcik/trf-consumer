<?php
/**
 * Created by PhpStorm.
 * User: michal
 * Date: 06/01/2019
 * Time: 23:37
 */

namespace TRFConsumer\Interfaces;


interface TRFConsumer
{
    public function consume(callable $msgProcess): void;
}