# Request control

Control major request access and set respective keywords to make routes unique for your services.

## 🤝 Controlling request at top level

```ini
; Request Payload Config
enableConfigRequest=1                   ; 1 = true / 0 = false
; Download CSV
enableDownloadRequest=1                 ; 1 = true / 0 = false
; Import CSV
enableImportRequest=1                   ; 1 = true / 0 = false
; Import CSV Sample
enableImportSampleRequest=1             ; 1 = true / 0 = false
; Listing available routes
enableRoutesRequest=1                   ; 1 = true / 0 = false
; Supplement feature controls
enableResponseCaching=1                 ; 1 = true / 0 = false
enableDropboxRequest=1                  ; 1 = true / 0 = false
enableCronRequest=1                     ; 1 = true / 0 = false
enableCustomRequest=1                   ; 1 = true / 0 = false
enableReloadRequest=1                   ; 1 = true / 0 = false
enableThirdPartyRequest=1               ; 1 = true / 0 = false
enableUploadRequest=1                   ; 1 = true / 0 = false
```

## Routes keyword

### Routes starting with configured params keyword

```ini
dropboxRequestRoutePrefix='cdn'
cronRequestRoutePrefix='cron'
customRequestRoutePrefix='custom'
thirdPartyRequestRoutePrefix='thirdParty'
uploadRequestRoutePrefix='upload'
```

### Routes ending with configured params keyword

```ini
configRequestRouteKeyword='config'      ; to append "/config" at the end of route
importRequestRouteKeyword='import'      ; to append "/import" at the end of route
importSampleRequestRouteKeyword='import-sample'
```

### Exact route

```ini
routesRequestRoute='routes'
reloadRequestRoutePrefix='reload'
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
