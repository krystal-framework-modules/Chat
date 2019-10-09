<?php

namespace Chat\Storage\MySQL;

use Krystal\Db\Sql\AbstractMapper;
use Krystal\Db\Sql\RawSqlFragment;
use Krystal\Db\Sql\QueryBuilder;
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
     * Fetch message receivers (last message, new message count and sender name)
     * 
     * @param int $receiverId An id of receiver - id of currently logged-in user
     * @return array
     */
    public function fetchReceivers($receiverId)
    {
        // Inner query to grab last message from a sender
        $lastMessageQuery = function(){
            $qb = new QueryBuilder();
            $qb->select(self::column('message'))
               ->from(self::getTableName())
               ->whereEquals(self::column('sender_id'), UserMapper::column('id'))
               ->andWhereEquals(self::column('read'), '0')
               ->orderBy(self::column('id'))
               ->desc()
               ->limit(1);

            return $qb->getQueryString();
        };

        // Columns to be selected
        $columns = array(
            UserMapper::column('id'),
            new RawSqlFragment(sprintf('(%s) AS `last`', $lastMessageQuery())),
            new RawSqlFragment(sprintf('COUNT(%s) AS `new`', self::column('id'))),
        );

        $db = $this->db->select($columns)
                       ->from(self::getTableName())
                       ->leftJoin(UserMapper::getTableName(), array(
                            UserMapper::column('id') => self::getRawColumn('sender_id')
                       ))
                       ->whereEquals(self::column('receiver_id'), $receiverId)
                       ->andWhereEquals(self::column('read'), '0')
                       ->groupBy(array(
                            UserMapper::column('name'),
                            'last'
                        ));

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
            'read',
            new RawSqlFragment(sprintf(
                '(%s = %s) AS `owner`', 'receiver_id', (int) $senderId
            ))
        );

        $values = array($senderId, $receiverId);

        $db = $this->db->select($columns)
                       ->from(self::getTableName())
                       ->whereIn('sender_id', $values)
                       ->andWhereIn('receiver_id', $values);

        return $db->queryAll();
    }
}
