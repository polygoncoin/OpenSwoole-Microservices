# Globally Required configs

```ini
ENVIRONMENT=0                           ; Environment PRODUCTION = 1 / DEVELOPMENT = 0
OUTPUT_PERFORMANCE_STATS=1              ; Add Performance Stats in JSON output: 1 = true / 0 = false
DISABLE_REQUESTS_VIA_PROXIES=1          ; 1 = true / 0 = false
```

## API authentication modes

```ini
; API authentication modes - Token / Session (Cookie based Sessions)
enableOpenRequest=1        ; 1 = true / 0 = false
enableAuthRequest=1        ; 1 = true / 0 = false
authMode='Token'            ; Token / Session (Cookie based Sessions)
sessionMode='File'          ; For Cookie based Session - 'File', 'MySql', 'PostgreSql', 'MongoDb', 'Redis', 'Memcached', 'Cookie'
enableConcurrentLogins=1    ; 1 = true / 0 = false
maxConcurrentLogins=2       ; simultaneous login limit
concurrentAccessInterval=60 ; Concurrent Access Interval in Seconds
```

## API Data Input/Output Representation

```ini
; Data Representation: JSON/XML/XSLT/HTML/PHP
iRepresentation='JSON'                  ; JSON/XML - Input Data Representation
oRepresentation='JSON'                  ; JSON/XML/XSLT/HTML/PHP - Output Data Representation
enableInputRepresentationAsQueryParam=1 ; 1 = true / 0 = false
enableOutputRepresentationAsQueryParam=1; 1 = true / 0 = false
enablePayloadInResponse=1               ; 1 = true / 0 = false
payloadKeyInResponse='Payload'
```

## Global Container Details

### Global cache

```ini
; (Redis) user <username> allcommands allkeys on ><password>
; used to save user and token related details
; Supported Containers - Redis / Memcached / MongoDb
gCacheServerType='Redis'
gCacheServerHostname='127.0.0.1'
gCacheServerPort=6379
gCacheServerUsername='ramesh'
gCacheServerPassword='shames11'
gCacheServerDatabase=0
gCacheServerTable='global_cache' ; For MongoDb
```

### Global Database

```ini
; Global Database details - global.sql
; Supported Containers - MySql / PostgreSql
gDbServerType='MySql'
gDbServerHostname='127.0.0.1'
gDbServerPort=3306
gDbServerUsername='root'
gDbServerPassword='shames11'
gDbServerDatabase='global'
gDbServerQueryPlaceholder='Named' ; Named(:param) / Unnamed(?)
```

## Other database configs

```ini
; Master database on global MySql server
masterDatabase='client_master'             ; contains all entities necessary for a new client

; Tables
clientsTable='client'
groupsTable='group'

; Sql query placeholder
queryPlaceholder='Named'            ; Named(:param) / Unnamed(?)

; Default perPage (records per page)
defaultPerPage=10
maxResultsPerPage=1000
```

## Global counter

As the heading describes below are the settings for global counter for the primary Key column of respective table. The setting when enable will generate a global auto-increment counter<br />

This enables identify client details easily while moving him from a common database for all to a dedicated client X database.

Enable below config for same.

```ini
; Global Auto-Increment counter details
enableGlobalCounter=0               ; 1 = true / 0 = false
gCounter='global_counter'           ; Key or Table
gCounterMode='Cache'                ; Globally configured Cache / Database
```

## Idempotent

This helps managing cache for idempotent request.

```ini
; Settings to avoid Idempotent request
idempotentSecret='changeme'         ; hash_hmac secret
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
