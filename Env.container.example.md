# Containers

In this package the Containers are Database / Caching tools used for maintaining dynamic data.<br />

## Server configuration Example

### Global Cache Server configuration (Redis)

```ini
; Cache Server configuration
gCacheServerType='Redis'
gCacheServerHostname='127.0.0.1'
gCacheServerPort=6379
gCacheServerUsername=''
gCacheServerPassword=''
gCacheServerDatabase=0
```

### Global Database Server configuration - global.sql

```ini
; Database Server configuration
gDbServerType='MySql'
gDbServerHostname='127.0.0.1'
gDbServerPort=3306
gDbServerUsername='username'
gDbServerPassword='password'
gDbServerDatabase='<global>'

; Tables in <global> database on the server
groupsTable='group'
clientsTable='client'
```

### Setting Cache / Database Server configuration in client table for working

These **Global Cache Server configuration (Redis)** and **Global Database Server configuration** config keys should be set in client table in below columns respectively for each client.

```SQL
`client`.`master_db_server_type` varchar(255) NOT NULL,
`client`.`master_db_hostname` varchar(255) NOT NULL,
`client`.`master_db_port` varchar(255) NOT NULL,
`client`.`master_db_username` varchar(255) NOT NULL,
`client`.`master_db_password` varchar(255) NOT NULL,
`client`.`master_db_database` varchar(255) NOT NULL,
`client`.`master_query_placeholder` varchar(255) NOT NULL,
`client`.`slave_db_server_type` varchar(255) NOT NULL,
`client`.`slave_db_hostname` varchar(255) NOT NULL,
`client`.`slave_db_port` varchar(255) NOT NULL,
`client`.`slave_db_username` varchar(255) NOT NULL,
`client`.`slave_db_password` varchar(255) NOT NULL,
`client`.`slave_db_database` varchar(255) NOT NULL,
`client`.`slave_query_placeholder` varchar(255) NOT NULL,
`client`.`master_cache_server_type` varchar(255) NOT NULL,
`client`.`master_cache_hostname` varchar(255) NOT NULL,
`client`.`master_cache_port` varchar(255) NOT NULL,
`client`.`master_cache_username` varchar(255) NOT NULL,
`client`.`master_cache_password` varchar(255) NOT NULL,
`client`.`master_cache_database` varchar(255) NOT NULL,
`client`.`master_cache_table` varchar(255) NOT NULL,
`client`.`slave_cache_server_type` varchar(255) NOT NULL,
`client`.`slave_cache_hostname` varchar(255) NOT NULL,
`client`.`slave_cache_port` varchar(255) NOT NULL,
`client`.`slave_cache_username` varchar(255) NOT NULL,
`client`.`slave_cache_password` varchar(255) NOT NULL,
`client`.`slave_cache_database` varchar(255) NOT NULL,
`client`.`slave_cache_table` varchar(255) NOT NULL,
```

- **Note**: Only the Key details in the environment file are to be set in columns of respective record. Eg. for column master_db_hostname the value to be set is 'gDbServerType' and not '127.0.0.1'. The configured values for the Key are picked from the env files.

The slave details can take same values as master if presently your system doesn't have such implementation.

## Setting Cache / Database Server configuration in client table for working

### Different database on DB server

If there is a requirement from client X to have a dedicated database like <database-x> on the DB server one can do this.<br /><br />

Make a new config variable as below and set this Key in the above table for client X record in client table.

```ini
cDbServerDatabase='<database-x>'
```

### Dedicated DB server

If the same client X in future prefer to have a dedicated database server one can do this as well.<br />

Make a new config variables as below and set this Key in the above table for client X record in client table.

- Client Cache

```ini
; Supported Containers - Redis / Memcached / MongoDb
cCacheServerType='Redis'
cCacheServerHostname='127.0.0.1'
cCacheServerPort=6379
cCacheServerUsername='username'
cCacheServerPassword='password'
cCacheServerDatabase=0
cCacheServerTable='client_001'      ; For MongoDb
```

- Dedicated database

```ini
; Supported Containers - MySql / PostgreSql
cDbServerType001='MySql'
cDbServerHostname001='127.0.0.1'
cDbServerPort001=3306
cDbServerUsername001='username'
cDbServerPassword001='password'
cDbServerDatabase001='client_001'
cDbServerQueryPlaceholder001='Named'; Named(:param) / Unnamed(?)

; Client Database table containing user login details
cDbServerDatabaseUsersTable='user'
```

### Going forward

One can on similar lines can configure slaves server details or a dedicated master / slave cache servers.

### Additional table detail in client database-x / database server
```ini
clientUsersTable='user'         ;Table in client database containing user details.
```

### The query_placeholder column

These column contains keys containing details about the way the queries are build to use data provided for SQL's'

```SQL
`client`.`master_query_placeholder` varchar(255) NOT NULL,
`client`.`slave_query_placeholder` varchar(255) NOT NULL,
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
; Supported Containers - Redis / Memcached / MySql / PostgreSql / MongoDb
sqlResultsCacheServerType='Redis'
sqlResultsCacheServerHostname='127.0.0.1'
sqlResultsCacheServerPort=6379
sqlResultsCacheServerUsername='username'
sqlResultsCacheServerPassword='password'
sqlResultsCacheServerDatabase=0
sqlResultsCacheServerTable='api_cache' ; For MySql / PostgreSql / MongoDb
```

## 🤝 Contributing

Issues and feature request are welcome.<br />
Feel free to share them on [issues page](https://github.com/polygoncoin/Microservices/issues)

## Author

- **Ramesh N. Jangid (Sharma)**

Github: [@polygoncoin](https://github.com/polygoncoin)

## 📝 License

Copyright © 2026 [Ramesh N. Jangid (Sharma)](https://github.com/polygoncoin).<br />
This project is [MIT](License) licensed.
