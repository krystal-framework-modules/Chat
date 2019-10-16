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
     * Mark all messages as read
     * 
     * @param int $senderId An id of sender
     * @param int $receiverId An id of receiver
     * @return boolean
     */
    public function markAsRead($senderId, $receiverId)
    {
        $db = $this->db->update(self::getTableName(), array('read' => '1'))
                       ->whereEquals('sender_id', $senderId)
                       ->andWhereEquals('receiver_id', $receiverId);

        return (bool) $db->execute(true);
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
                       ->append('WHERE')
                       ->openBracket()
                       ->equals('sender_id', $receiverId)
                       ->andWhereEquals('receiver_id', $senderId)
                       ->closeBracket()
                       ->rawOr()
                       ->openBracket()
                       ->equals('sender_id', $senderId)
                       ->andWhereEquals('receiver_id', $receiverId)
                       ->closeBracket();

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
     * Creates query to grab main data
     * 
     * @param int $receiverId
     * @return string
     */
    private function createNewCountQuery($receiverId)
    {
        // Inner query to grab last message from a sender
        $lastMessageQuery = function(){
            $qb = new QueryBuilder();
            $qb->select(self::column('message'))
               ->from(self::getTableName())
               ->whereEquals(self::column('sender_id'), UserMapper::column('id'))
               ->orderBy(self::column('id'))
               ->desc()
               ->limit(1);

            return $qb->getQueryString();
        };

        // Inner query to count unread message
        $countQuery = function($receiverId){
            $qb = new QueryBuilder();
            $qb->select()
               ->count(self::column('id'))
               ->from(self::getTableName())
               ->whereEquals(self::column('sender_id'), UserMapper::column('id'))
               ->andWhereEquals(self::column('receiver_id'), $receiverId)
               ->andWhereEquals(self::column('read'), '0');

            return $qb->getQueryString();
        };
        
        $qb = new QueryBuilder();
        $qb->openBracket()
           ->select(array(
                UserMapper::column('id'),
                UserMapper::column('name'),
                UserMapper::column('avatar')
           ))
           ->expression($lastMessageQuery(), 'last')
           ->expression($countQuery($receiverId), 'new')
           ->from(self::getTableName())
           // User relation
           ->leftJoin(UserMapper::getTableName(), array(
                UserMapper::column('id') => self::column('sender_id'),
           ))
           ->whereEquals(self::column('receiver_id'), $receiverId)
           ->groupBy(array(
                UserMapper::column('id'),
                UserMapper::column('name'),
                'last',
                'new'
            ))
           ->closeBracket();

        return $qb->getQueryString();
    }

    /**
     * Create query to grab unread messages
     * 
     * @param int $senderId
     * @return string
     */
    private function createUnreadQuery($senderId)
    {
        // Inner query to grab last message from a sender
        $lastMessageQuery = function(){
            $qb = new QueryBuilder();
            $qb->select(self::column('message'))
               ->from(self::getTableName())
               ->whereEquals(self::column('receiver_id'), UserMapper::column('id'))
               ->orderBy(self::column('id'))
               ->desc()
               ->limit(1);

            return $qb->getQueryString();
        };

        // Inner query to grab singular contacts
        $internalQuery = function($senderId){
            $subQuery = new QueryBuilder();
            $subQuery->select(1)
                     ->from(self::getTableName(), 'um2')
                     ->whereEquals('um2.sender_id', 'um.receiver_id');

            $qb = new QueryBuilder();
            $qb->openBracket()
               ->select('um.receiver_id', true)
               ->from(self::getTableName(), 'um')
               ->whereEquals('um.sender_id', $senderId)
               ->rawAnd()
               ->notExists($subQuery->getQueryString())
               ->closeBracket();

            return $qb->getQueryString();
        };

        $qb = new QueryBuilder();
        $qb->openBracket()
           ->select(array(
                UserMapper::column('id'),
                UserMapper::column('name'),
                UserMapper::column('avatar')
           ))
           ->expression($lastMessageQuery(), 'last')
           ->expression(0, 'new')
           ->from(self::getTableName())
           // User relation
           ->leftJoin(UserMapper::getTableName(), array(
                UserMapper::column('id') => self::column('receiver_id'),
           ))
           ->whereIn(self::column('receiver_id'), new RawSqlFragment($internalQuery($senderId)))
           ->groupBy(array(
                UserMapper::column('id'),
                UserMapper::column('name'),
                'last',
                'new'
            ))
           ->closeBracket();

        return $qb->getQueryString();
    }

    /**
     * Fetch message receivers (last message, new message count and sender name)
     * 
     * @param int $receiverId An id of receiver - id of currently logged-in user
     * @return array
     */
    public function fetchReceivers($receiverId)
    {
        $receiverId = (int) $receiverId;

        $query = sprintf('%s UNION %s', $this->createNewCountQuery($receiverId), $this->createUnreadQuery($receiverId));

        return $this->db->raw($query)
                        ->queryAll();
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
            self::column('id'),
            self::column('message'),
            self::column('datetime'),
            self::column('read'),
            self::column('avatar'),
            new RawSqlFragment(sprintf(
                '(%s = %s) AS `owner`', 'receiver_id', (int) $senderId
            ))
        );

        $values = array($senderId, $receiverId);

        $db = $this->db->select($columns)
                       ->from(self::getTableName())
                       // User relation
                       ->leftJoin(UserMapper::getTableName(), array(
                            UserMapper::column('id') => 'receiver_id'
                       ))
                       ->whereIn('sender_id', $values)
                       ->andWhereIn('receiver_id', $values)
                       ->orderBy(self::column('id'));

        return $db->queryAll();
    }
}
