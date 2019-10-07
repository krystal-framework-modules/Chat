<?php

namespace Chat\Storage\MySQL;

use Krystal\Db\Sql\AbstractMapper;
use User\Storage\MySQL\UserMapper;

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
     * Deletes a dialog between two users
     * 
     * @param int $senderId An id of sender
     * @param int $receiverId An id of receiver
     * @return boolean
     */
    public function deleteDialog($senderId, $receiverId)
    {
        $db = $this->db->delete()
                       ->from(self::getTableName())
                       ->whereEquals('sender_id', $senderId)
                       ->andWhereEquals('receiver_id', $receiverId);

        return (bool) $db->execute(true);
    }

    /**
     * Count amount of new messages
     * 
     * @param int $ownerId Profile id
     * @return int
     */
    public function countNew($ownerId)
    {
        $db = $this->db->select()
                       ->count('id')
                       ->from(self::getTableName())
                       ->whereEquals('sender_id', $ownerId)
                       ->andWhereEquals('read', '0');

        return (int) $db->queryScalar();
    }

    /**
     * Fetch message receivers
     * 
     * @param int $receiverId An id of receiver
     * @return array
     */
    public function fetchReceivers($receiverId)
    {
        // Columns to be selected
        $columns = array(
            UserMapper::column('id'),
            UserMapper::column('name')
        );

        $db = $this->db->select($columns, true)
                       ->from(self::getTableName())
                       ->leftJoin(UserMapper::getTableName(), array(
                            UserMapper::column('id') => self::getRawColumn('sender_id')
                       ))
                       ->whereEquals(self::column('receiver_id'), $receiverId)
                       ->orderBy(self::column('id'))
                       ->desc();

        return $db->queryAll();
    }

    /**
     * Fetch a dialog of two users
     * 
     * @param int $senderId An id of sender
     * @param int $receiverId An id of receiver
     * @return array
     */
    public function fetchDialog($receiverId, $senderId)
    {
        // Columns to be selected
        $columns = array(
            'id',
            'message',
            'datetime',
            'read'
        );

        $values = array($senderId, $receiverId);

        $db = $this->db->select($columns)
                       ->from(self::getTableName())
                       ->whereIn('sender_id', $values)
                       ->andWhereIn('receiver_id', $values)
                       ->orderBy('id')
                       ->desc();

        return $db->queryAll();
    }
}
