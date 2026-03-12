# Container

In this package the Container are Database / Caching tools used for maintaining dynamic data.<br />

## Server configuration Example

### Global Cache Server configuration (Redis)

```ini
; Cache Server configuration
gCacheServerType='Redis'
gCacheServerHostname='127.0.0.1'
gCacheServerPort=6379
gCacheServerUsername=''
gCacheServerPassword=''
gCacheServerDB=0
```

### Global Database Server configuration - global.sql

```ini
; Database Server configuration
gDbServerType='MySql'
gDbServerHostname='127.0.0.1'
gDbServerPort=3306
gDbServerUsername='username'
gDbServerPassword='password'
gDbServerDB='<global>'

; Tables in <global> database on the server
groupsTable='group'
customerTable='customer'
```

### Setting Cache / Database Server configuration in customer table for working

These **Global Cache Server configuration (Redis)** and **Global Database Server configuration** config keys should be set in customer table in below columns respectively for each customer.

```SQL
`customer`.`master_db_server_type` varchar(255) NOT NULL,
`customer`.`master_db_server_hostname` varchar(255) NOT NULL,
`customer`.`master_db_server_port` varchar(255) NOT NULL,
`customer`.`master_db_server_username` varchar(255) NOT NULL,
`customer`.`master_db_server_password` varchar(255) NOT NULL,
`customer`.`master_db_server_db` varchar(255) NOT NULL,
`customer`.`master_db_server_query_placeholder` varchar(255) NOT NULL,
`customer`.`slave_db_server_type` varchar(255) NOT NULL,
`customer`.`slave_db_server_hostname` varchar(255) NOT NULL,
`customer`.`slave_db_server_port` varchar(255) NOT NULL,
`customer`.`slave_db_server_username` varchar(255) NOT NULL,
`customer`.`slave_db_server_password` varchar(255) NOT NULL,
`customer`.`slave_db_server_db` varchar(255) NOT NULL,
`customer`.`slave_db_server_query_placeholder` varchar(255) NOT NULL,
`customer`.`master_cache_server_type` varchar(255) NOT NULL,
`customer`.`master_cache_server_hostname` varchar(255) NOT NULL,
`customer`.`master_cache_server_port` varchar(255) NOT NULL,
`customer`.`master_cache_server_username` varchar(255) NOT NULL,
`customer`.`master_cache_server_password` varchar(255) NOT NULL,
`customer`.`master_cache_server_db` varchar(255) NOT NULL,
`customer`.`master_cache_server_table` varchar(255) NOT NULL,
`customer`.`slave_cache_server_type` varchar(255) NOT NULL,
`customer`.`slave_cache_server_hostname` varchar(255) NOT NULL,
`customer`.`slave_cache_server_port` varchar(255) NOT NULL,
`customer`.`slave_cache_server_username` varchar(255) NOT NULL,
`customer`.`slave_cache_server_password` varchar(255) NOT NULL,
`customer`.`slave_cache_server_db` varchar(255) NOT NULL,
`customer`.`slave_cache_server_table` varchar(255) NOT NULL,
```

- **Note**: Only the Key details in the environment file are to be set in columns of respective record. Eg. for column master_db_server_hostname the value to be set is 'gDbServerType' and not '127.0.0.1'. The configured values for the Key are picked from the env files.

The slave details can take same values as master if presently your system doesn't have such implementation.

## Setting Cache / Database Server configuration in customer table for working

### Different database on DB server

If there is a requirement from customer X to have a dedicated database like <database-x> on the DB server one can do this.<br /><br />

Make a new config variable as below and set this Key in the above table for customer X record in customer table.

```ini
cDatabaseServerDB='<database-x>'
```

### Dedicated DB server

If the same customer X in future prefer to have a dedicated database server one can do this as well.<br />

Make a new config variables as below and set this Key in the above table for customer X record in customer table.

- Customer Cache

```ini
; Supported Container - Redis / Memcached / MongoDb
cCacheServerType='Redis'
cCacheServerHostname='127.0.0.1'
cCacheServerPort=6379
cCacheServerUsername='username'
cCacheServerPassword='password'
cCacheServerDB=0
cCacheServerTable='customer_001'      ; For MongoDb
```

- Dedicated database

```ini
; Supported Container - MySql / PostgreSql
cDbServerType001='MySql'
cDbServerHostname001='127.0.0.1'
cDbServerPort001=3306
cDbServerUsername001='username'
cDbServerPassword001='password'
cCacheServerDB001='customer_001'
cDbServerQueryPlaceholder001='Named'; Named(:param) / Unnamed(?)

; Customer Database table containing user login details
cDatabaseServerDBUsersTable='user'
```

### Going forward

One can on similar lines can configure slaves server details or a dedicated master / slave cache servers.

### Additional table detail in customer database-x / database server
```ini
customerUsersTable='user'         ;Table in customer database containing user details.
```

### The query_placeholder column

These column contains keys containing details about the way the queries are build to use data provided for SQL's'

```SQL
`customer`.`master_db_server_query_placeholder` varchar(255) NOT NULL,
`customer`.`slave_db_server_query_placeholder` varchar(255) NOT NULL,
```

#### Named(:param)

```SQL
-- Named(:param)
INSERT INTO `user` SET `firstname` = :firstname;
```

#### Unnamed(?)

```SQL
-- Unnamed(?)
INSERT INTO `user` SET `firstname` = ?;
```

## Global Cache hit configurations can be set as below.

Below settings are not to be configured in any table. They are used as it is. Only need to make required config value changes in below.

```ini
; Supported Container - Redis / Memcached / MySql / PostgreSql / MongoDb
queryCacheServerType='Redis'
queryCacheServerHostname='127.0.0.1'
queryCacheServerPort=6379
queryCacheServerUsername='username'
queryCacheServerPassword='password'
queryCacheServerDB=0
queryCacheServerTable='api_cache' ; For MySql / PostgreSql / MongoDb
```

## Contributing

Issues and feature request are welcome.<br />
Feel free to share them on [issues page](https://github.com/polygoncoin/OpenSwoole-Microservices/issues)

## Author

- **Ramesh N. Jangid (Sharma)**

Github: [@polygoncoin](https://github.com/polygoncoin)

## License

Copyright © 2026 [Ramesh N. Jangid (Sharma)](https://github.com/polygoncoin).<br />
This project is [MIT](License) licensed.
