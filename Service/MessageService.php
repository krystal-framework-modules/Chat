<?php

namespace Chat\Service;

use Krystal\Application\Model\AbstractService;
use Krystal\Stdlib\VirtualEntity;
use Chat\Storage\MySQL\MessageMapper;

final class MessageService extends AbstractService
{
    /**
     * Any compliant mapper
     * 
     * @var \Chat\Storage\MySQL\MessageMapper
     */
    private $messageMapper;

    /**
     * State initialization
     * 
     * @param \Chat\Storage\MySQL\MessageMapper $messageMapper     
     * @return void
     */
    public function __construct(MessageMapper $messageMapper)
    {
        $this->messageMapper = $messageMapper;
    }

    /**
     * Fetch a dialog of two users
     * 
     * @param int $senderId An id of sender
     * @param int $receiverId An id of receiver
     * @return array
     */
    public function fetchDialog($senderId, $receiverId)
    {
        return $this->messageMapper->fetchDialog($senderId, $receiverId);
    }
}
