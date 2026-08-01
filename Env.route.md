# Request Control

Control major request access and set respective keywords to make routes unique for your services.

## Route keyword

### Route starting with configured params keyword

```ini
dropboxRequestRoutePrefix='cdn'
cronRequestRoutePrefix='cron'
customRequestRoutePrefix='custom'
thirdPartyRequestRoutePrefix='thirdParty'
uploadRequestRoutePrefix='upload'
```

### Route ending with configured params keyword

```ini
; Explain Route & its Payload
explainRequestRouteKeyword='explain'    ; to append "/explain" at the end of route
importRequestRouteKeyword='import'      ; to append "/import" at the end of route
importSampleRequestRouteKeyword='import-sample'
```

### Exact route

```ini
routesRequestRoute='routes'
reloadRequestRoutePrefix='reload'
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
