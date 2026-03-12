# Global Container Details

## Global cache

```ini
; (Redis) user <username> allcommands allkeys on ><password>
; used to save user and token related details
; Supported Containers - Redis / Memcached / MongoDb
gCacheServerType='Redis'
gCacheServerHostname='127.0.0.1'
gCacheServerPort=6379
gCacheServerUsername='ramesh'
gCacheServerPassword='shames11'
gCacheServerDB=0
gCacheServerTable='global_cache' ; For MongoDb
```

## Global Database

```ini
; Global Database details - global.sql
; Supported Containers - MySql / PostgreSql
gDbServerType='MySql'
gDbServerHostname='127.0.0.1'
gDbServerPort=3306
gDbServerUsername='root'
gDbServerPassword='shames11'
gDbServerDB='global'
gDbServerQueryPlaceholder='Named' ; Named(:param) / Unnamed(?)
```

## Other database configs

```ini
; Master database on global MySql server
customerMasterDB='customer_master'             ; contains all entities necessary for a new customer

; Tables
customerTable='customer'
groupsTable='group'

; Sql query placeholder
queryPlaceholder='Named'            ; Named(:param) / Unnamed(?)
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
