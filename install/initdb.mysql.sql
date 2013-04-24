CREATE USER '%MYSQL_USER%'@'%' IDENTIFIED BY '%MYSQL_PASSWORD%';

GRANT USAGE ON * . * TO '%MYSQL_USER%'@'%' IDENTIFIED BY '%MYSQL_PASSWORD%' WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0 ;

CREATE DATABASE IF NOT EXISTS `%MYSQL_DBNAME%` ;

GRANT ALL PRIVILEGES ON `%MYSQL_DBNAME%` . * TO '%MYSQL_USER%'@'%';

