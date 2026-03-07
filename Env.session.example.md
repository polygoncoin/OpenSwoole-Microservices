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
MYSQL_HOSTNAME='localhost'
MYSQL_PORT=3306
MYSQL_USERNAME='root'
MYSQL_PASSWORD='shames11'
MYSQL_DATABASE='session_db'
MYSQL_TABLE='sessions'
```

## PostgreSQL based PHP Session related configs

```ini
; PostgreSQL
PGSQL_HOSTNAME='localhost'
PGSQL_PORT=5432
PGSQL_USERNAME=''
PGSQL_PASSWORD=''
PGSQL_DATABASE='session_db'
PGSQL_TABLE='sessions'
```

## MongoDB based PHP Session related configs

```ini
; MongoDB
MONGODB_HOSTNAME='localhost'
MONGODB_PORT=27017
MONGODB_USERNAME=''
MONGODB_PASSWORD=''
MONGODB_DATABASE='session_db'
MONGODB_COLLECTION='sessions'
```

## Redis based PHP Session related configs

```ini
; Redis
REDIS_HOSTNAME='localhost'
REDIS_PORT=6379
REDIS_USERNAME=''
REDIS_PASSWORD=''
REDIS_DATABASE=0;
```

## Memcached based PHP Session related configs

```ini
; Memcached
MEMCACHED_HOSTNAME='localhost'
MEMCACHED_PORT=11211
```

## Cookie based PHP Session related configs

```ini
; Cookie
sessionDataName='PHPSESSDATA'   ; For sessionMode Cookie
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
