<?php

class SGConfig
{
    private static $values = array();

    public static function set($key, $value, $forced = true)
    {
        self::$values[$key] = $value;

        if ($forced)
        {
            $sgdb = SGDatabase::getInstance();
            $res = $sgdb->query('INSERT INTO '.SG_CONFIG_TABLE_NAME.' (ckey, cvalue) VALUES (%s, %s) ON DUPLICATE KEY UPDATE cvalue = %s', array($key, $value, $value));
            return $res;
        }

        return true;
    }

    public static function get($key, $forced = false)
    {
        if (!$forced)
        {
            if (isset(self::$values[$key]))
            {
                return self::$values[$key];
            }

            if (defined($key))
            {
                return constant($key);
            }
        }
        
        $sgdb = SGDatabase::getInstance();
        $data = $sgdb->query('SELECT cvalue, NOW() FROM '.SG_CONFIG_TABLE_NAME.' WHERE ckey = %s', array($key));

        if (!$data)
        {
            return null;
        }

        self::$values[$key] = $data[0]['cvalue'];
        return $data[0]['cvalue'];
    }

    public static function getAll()
    {
        $sgdb = SGDatabase::getInstance();
        $configs = $sgdb->query('SELECT * FROM '.SG_CONFIG_TABLE_NAME);

        if (!$configs)
        {
            return null;
        }
        
        $currentConfigs = array();
        foreach ($configs as $config)
        {
            self::$values[$config['ckey']] = $config['cvalue'];
            $currentConfigs[$config['ckey']] = $config['cvalue'];
        }

        return $currentConfigs;
    }
}