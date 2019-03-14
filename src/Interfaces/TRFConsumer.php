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
    /**
     * @param string $queue Queue to consume
     * @param callable $msgProcess
     */
    public function consume(string $queue, callable $msgProcess): void;
}