<?php
namespace ManaPHP\Logger\Adapter\Db;

/**
 * Class ManaPHP\Logger\Adapter\Db\Model
 *
 * @package logger
 */
class Model extends \ManaPHP\Db\Model
{
    /**
     * @var int
     */
    public $log_id;

    /**
     * @var string
     */
    public $level;

    /**
     * @var int
     */
    public $user_id;

    /**
     * @var string
     */
    public $user_name;

    /**
     * @var string
     */
    public $module;

    /**
     * @var string
     */
    public $controller;

    /**
     * @var string
     */
    public $action;

    /**
     * @var string
     */
    public $message;

    /**
     * @var string
     */
    public $ip;

    /**
     * @var int
     */
    public $created_time;

    public static function getSource($context = null)
    {
        return 'manaphp_log';
    }
}