<?php

/**
 * Global memcached support.
 */

class Cache {
    private static $memcached = null;

    static public function getInstance() {
        if (self::$memcached === null) {
            self::$memcached = new Memcached;

            global $memcache_servers;
            ld($memcache_servers);
            if (is_array($memcache_servers)) {
                foreach ($memcache_servers as $server) {
                    self::$memcached->addServer($server[0], $server[1]);
                }
            }
        }

        return self::$memcached;
    }
}

function mem_get($group, $key) {
    global $memprefix;
    global $memprefix_groups;
    global $domain; // FIXME: Fill this with propper domain.

    $memcached = Cache::getInstance();
    $groupprefix = (isset($memprefix_groups[$group]) ? $memprefix_groups[$group] : '');
    $value = $memcached->get($memprefix.$domain.$groupprefix.$key);
    // ($value === FALSE) ? $mem_m++ : $mem_h++; // Hits/Miss stats.
    return $value;
}

function mem_set($group, $key, $value, $ttl) {
    global $memprefix;
    global $memprefix_groups;
    global $domain;

    $memcached = Cache::getInstance();
    $groupprefix = (isset($memprefix_groups[$group]) ? $memprefix_groups[$group] : '');
    $memCachedTest = $memcached->add($memprefix.$domain.$groupprefix.$key, $value, $ttl);
    ld($memcached->getAllKeys());
    return $memCachedTest;
}

function mem_reset($group = '') {
    global $memprefix;
    global $memprefix_groups;

    // Generate a short random hash to use as the prefix.
    // Keeping this short to live with Memcached key length limits (250 chars).
    $hash  = rand(1, 900);
    $hash .= substr(str_shuffle('abcdefghijlkmnopqrstuvwxyz'), 0, 5);

    if ($group == '') {
        // Global reset.
        $memprefix = $hash;
    }
    else {
        // Only reset a specific group.
        $memprefix_groups[$group] = $hash;
    }
}

function mem_delete($group, $key) {
    global $memprefix;
    global $memprefix_groups;
    global $domain;

    $memcached = Cache::getInstance();
    $groupprefix = (isset($memprefix_groups[$group]) ? $memprefix_groups[$group] : '');
    return $memcached->delete($memprefix.$domain.$groupprefix.$key);
}
