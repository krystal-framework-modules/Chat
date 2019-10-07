<?php

namespace Chat;

use Krystal\Application\Module\AbstractModule;
use Chat\Service\MessageService;

final class Module extends AbstractModule
{
    /**
     * Returns routes of this module
     * 
     * @return array
     */
    public function getRoutes()
    {
        return include(__DIR__) . '/Config/routes.php';
    }

    /**
     * Returns prepared service instances of this module
     * 
     * @return array
     */
    public function getServiceProviders()
    {
        return array(
            'messageService' => new MessageService($this->createMapper('\Chat\Storage\MySQL\MessageMapper'))
        );
    }
}
