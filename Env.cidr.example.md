# Allowed IPs in CIDR format

Classless Inter-Domain Routing (CIDR) is a method for assigning IP addresses to devices on the internet. Multiple CIDR separated by comma can be set here.

## More about CIDR
To learn more about CIDR one can [Check Google](https://www.google.com/search?q=CIDR)

## 🤝 Enable CIDR

To enable CIDR checks one can do this as below in .env.cidr
```ini
enableCidrCheck=0                      ; 1 = true / 0 = false in
```

## 🤝 Setting top level CIDR

Below are top level CIDR settings for a set of system routes (starting / ending with)

### Routes starting with configured params keyword for respective comment

```ini
dropboxRestrictedCidr='0.0.0.0/0'       ; dropboxRequestRoutePrefix in .env.enable
cronRestrictedCidr='0.0.0.0/0'          ; cronRequestRoutePrefix in .env.enable
customRestrictedCidr='0.0.0.0/0'        ; customRequestRoutePrefix in .env.enable
thirdPatyRestrictedCidr='0.0.0.0/0'     ; thirdPartyRequestRoutePrefix in .env.enable
uploadRestrictedCidr='0.0.0.0/0'        ; uploadRequestRoutePrefix in .env.enable
```

### Routes ending with configured params keyword for respective comment

```ini
configRestrictedCidr='0.0.0.0/0'        ; configRequestRouteKeyword in .env.enable
exportRestrictedCidr='0.0.0.0/0'        ;
importRestrictedCidr='0.0.0.0/0'        ; importRequestRouteKeyword in .env.enable
importSampleRestrictedCidr='0.0.0.0/0'  ; importSampleRequestRouteKeyword in .env.enable
```

### Routes exact configured params keyword for respective comment

```ini
routesRestrictedCidr='0.0.0.0/0'        ; routesRequestRoute in .env.enable
reloadRestrictedCidr='0.0.0.0/0'        ; reloadRequestRoutePrefix in .env.enable
```

## 🤝 Configuring in DB Tables

To enable CIDR settings at Client / Group / User level one can set them in respective table and record

```SQL
-- Client level
`client`.`allowed_cidr` VARCHAR(250) DEFAULT '0.0.0.0/0',

-- Group level
`group`.`allowed_cidr` VARCHAR(250) DEFAULT '0.0.0.0/0',

-- User level
`user`.`allowed_cidr` VARCHAR(250) DEFAULT '0.0.0.0/0',
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
