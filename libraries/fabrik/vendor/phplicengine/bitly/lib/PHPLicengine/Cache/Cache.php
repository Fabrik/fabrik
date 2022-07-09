<?php

// Cache.php
#################################################
##
## PHPLicengine
##
#################################################
## Copyright 2009-{current_year} PHPLicengine
## 
## Licensed under the Apache License, Version 2.0 (the "License");
## you may not use this file except in compliance with the License.
## You may obtain a copy of the License at
##
##    http://www.apache.org/licenses/LICENSE-2.0
##
## Unless required by applicable law or agreed to in writing, software
## distributed under the License is distributed on an "AS IS" BASIS,
## WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
## See the License for the specific language governing permissions and
## limitations under the License.
#################################################

namespace PHPLicengine\Cache;
use PHPLicengine\Exception\CacheException;

class Cache {
 
      private $config;
      
      public function __construct (array $config)
      {
             $this->config = $config;       
      }

      /*
      This class uses Doctrine Cache. You can look at its doc to add more cache type.
      Whatever option you need to setup the cache type, must be passed as array to constructor.
      https://www.doctrine-project.org/projects/doctrine-cache/en/1.8/index.html
      */
      public function getCache ()
      {
             switch ($this->config['type']) {
                     case 'apc':
                          $cache = new \Doctrine\Common\Cache\ApcCache();
                     break;
                     case 'file':
                          $cache = new \Doctrine\Common\Cache\FilesystemCache($this->config['path']);
                     break;
                     case 'sqlite3':
                          $db = new \SQLite3($this->config['sqlite3_db']);
                          $cache = new \SQLite3Cache($db, $this->config['sqlite3_table']);
                     break;
                     case 'xcache':
                          $cache = new \Doctrine\Common\Cache\XcacheCache();
                      break;
                      default:
                          throw new CacheException('Invalid cache system');
                      break;
             } 
             return $cache;
      }
}
