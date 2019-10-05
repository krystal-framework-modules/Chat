<?php

namespace Chat\Service;

use Krystal\Application\Model\AbstractService;
use Krystal\Stdlib\VirtualEntity;
use Krystal\Date\TimeHelper;
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
     * Sends a message
     * 
     * @param int $senderId An id of sender
     * @param int $receiverId An id of receiver
     * @param string $message
     * @return boolean
     */
    public function sendMessage($senderId, $receiverId, $message)
    {
        $data = array(
            'sender_id' => $senderId,
            'receiver_id' = $receiverId,
            'message' => $message,
            'datetime' => TimeHelper::getNow()
        );

        return $this->messageMapper->persist($data);
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
