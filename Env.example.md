# Globally Required configs

```ini
ENVIRONMENT=0                           ; Environment PRODUCTION = 1 / DEVELOPMENT = 0
OUTPUT_PERFORMANCE_STATS=1              ; Add Performance Stats in JSON output: 1 = true / 0 = false
DISABLE_REQUESTS_VIA_PROXIES=1          ; 1 = true / 0 = false
```

## API authentication modes

```ini
; API authentication modes - Token / Session (Cookie based Sessions)
authMode='Token'            ; Token / Session (Cookie based Sessions)
sessionMode='File'          ; For Cookie based Session - 'File', 'MySql', 'PostgreSql', 'MongoDb', 'Redis', 'Memcached', 'Cookie'
maxConcurrentLogin=2       ; simultaneous login limit
concurrentAccessInterval=60 ; Concurrent Access Interval in Seconds
```

## API Data Input/Output Representation

```ini
; Data Representation: JSON/XML/XSLT/HTML/PHP
iRepresentation='JSON'                  ; JSON/XML - Input Data Representation
oRepresentation='JSON'                  ; JSON/XML/XSLT/HTML/PHP - Output Data Representation
payloadKeyInResponse='Payload'
```

## Other database configs

```ini
; Default perPage (records per page)
defaultPerPage=10
maxResultsPerPage=1000
```

## Global counter

As the heading describes below are the settings for global counter for the primary Key column of respective table. The setting when enable will generate a global auto-increment counter<br />

This enables identify Customer Data easily while moving him from a common database for all to a dedicated customer X database.

Set below config.

```ini
; Global Auto-Increment counter detail
gCounter='global_counter'           ; Key or Table
gCounterMode='Cache'                ; Globally configured Cache / Database
```

## Idempotent

This helps managing cache for idempotent request.

```ini
; Settings to avoid Idempotent request
idempotentSecret='changeme'         ; hash_hmac secret
```

## Contributing

Issues and feature request are welcome.<br />
Feel free to share them on [issues page](https://github.com/polygoncoin/Openswoole-Microservices/issues)

## Author

- **Ramesh N. Jangid (Sharma)**

Github: [@polygoncoin](https://github.com/polygoncoin)

## License

Copyright © 2026 [Ramesh N. Jangid (Sharma)](https://github.com/polygoncoin).<br />
This project is [MIT](License) licensed.
