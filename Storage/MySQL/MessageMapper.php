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
}
