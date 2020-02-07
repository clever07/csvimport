<?php

declare(strict_types=1);

namespace App\Rabbit;

use OldSound\RabbitMqBundle\RabbitMq\Producer;

class ProductProducer extends Producer
{
    /**
     * @var Producer
     */
    private $producer;

    /**
     * @param Producer $producer
     */
    public function __construct(Producer $producer)
    {
        $this->producer = $producer;
    }

    /**
     * @param $pathFile
     */
    public function add($pathFile) {
        $message = [
            'path_file' => $pathFile,
            'dateTimeUploaded' => date('Y-m-d H:i:s')
        ];
        $this->producer->publish(json_encode($message));
    }

}