<?php

namespace Chat\Storage\MySQL;

use Krystal\Db\Sql\AbstractMapper;

final class MessageMapper extends AbstractMapper
{
    /**
     * {@inheritDoc}
     */
    public static function getTableName()
    {
        return 'users_messages';
    }

    /**
     * Returns primary column name for current mapper
     * 
     * @return string
     */
    protected function getPk()
    {
        return 'id';
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
        // Columns to be selected
        $columns = array(
            'id',
            'message',
            'datetime',
            'read'
        );

        $db = $this->db->select($columns)
                       ->from(self::getTableName())
                       ->whereEquals('sender_id', $senderId)
                       ->andWhereEquals('receiver_id', $receiverId)
                       ->orderBy('id')
                       ->desc();

        return $db->queryAll();
    }
}
