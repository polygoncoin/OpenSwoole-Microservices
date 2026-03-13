# PHP Session

Collection of Mostly used Session Handlers

- Supports File / MySql / PostgreSql / MongoDb / Redis / Memcached / Cookie based Session Handlers
- Supports Readonly mode as well for all the above mentioned Session Handlers

## PHP Session related configs

```ini
; Domain name
sessionDomain='localhost'
sessionName='PHPSESSID' ; Default
sessionMaxLifetime=3600
sessionSavePath='sessions'
```

### Encrypting PHP Session data settings

```ini
; Value=base64_encode(openssl_random_pseudo_bytes(32))
ENCRYPTION_PASS_PHRASE='H7OO2m3qe9pHyAHFiERlYJKnlTMtCJs9ZbGphX9NO/c='

; Value=base64_encode(openssl_random_pseudo_bytes(16))
ENCRYPTION_IV='HnPG5az9Xaxam9G9tMuRaw=='
```

## MySQL based PHP Session related configs

```ini
; MySQL
mySqlServerHostname='localhost'
mySqlServerPort=3306
mySqlServerUsername='root'
mySqlServerPassword='shames11'
mySqlServerDB='session_db'
mySqlServerTable='sessions'
```

## PostgreSQL based PHP Session related configs

```ini
; PostgreSQL
pgSqlServerHostname='localhost'
pgSqlServerPort=5432
pgSqlServerUsername=''
pgSqlServerPassword=''
pgSqlServerDB='session_db'
pgSqlServerTable='sessions'
```

## MongoDB based PHP Session related configs

```ini
; MongoDB
mongoDbServerHostname='localhost'
mongoDbServerPort=27017
mongoDbServerUsername=''
mongoDbServerPassword=''
mongoDbServerDB='session_db'
mongoDbServerCollection='sessions'
```

## Redis based PHP Session related configs

```ini
; Redis
redisServerHostname='localhost'
redisServerPort=6379
redisServerUsername=''
redisServerPassword=''
redisServerDB=0;
```

## Memcached based PHP Session related configs

```ini
; Memcached
memcachedServerHostname='localhost'
memcachedServerPort=11211
```

## Cookie based PHP Session related configs

```ini
; Cookie
sessionDataName='PHPSESSDATA'   ; For sessionMode Cookie
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
