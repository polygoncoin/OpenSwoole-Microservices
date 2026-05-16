# Rate Limiting

Rate Limiting is a method of restricting traffic for a set window of time. The time windows unit used here is No. of Seconds to allow a particular number of request in respective param.

## More about Rate Limiting
To learn more about Rate Limiting one can [Check Google](https://www.google.com/search?q=Rate%20Limiting)

## Setting Rate Limiting key's to be used as identifier with Function(s) combined

```ini
; Rate limit open traffic (not limited by allowed IPs/CIDR and allowed Rate Limit to user)
rateLimitIPPrefix='IPRL:'
; Customer based Rate Limitng (GRL) Key prefix used in Redis
rateLimitCustomerPrefix='CRL:'
; Group based Rate Limitng (GRL) Key prefix used in Redis
rateLimitGroupPrefix='GRL:'
; User based Rate Limitng (URL) Key prefix used in Redis
rateLimitUserPrefix='URL:'
; Route based Rate Limiting (RRL) Key prefix used in Redis
rateLimitRoutePrefix='RRL:'
; User Per IP based Rate Limiting (UIRL) Key prefix used in Redis
rateLimitUserPerIpPrefix='UIRL:'
; User Per IP based Rate Limiting (UIRL) Key prefix used in Redis
rateLimitUserRequestPrefix='URRL:'
```

## Setting Rate Limiting key's Limit with window in seconds

```SQL
`customer`.`rateLimitIPMaxRequest` INT DEFAULT NULL, -- ; Max request allowed per IP
`customer`.`rateLimitIPMaxRequestWindow` INT DEFAULT NULL, -- ; Window for Max request allowed per IP
`customer`.`rateLimitMaxUserPerIp` INT DEFAULT NULL, -- ; Max User allowed per IP
`customer`.`rateLimitMaxUserPerIpWindow` INT DEFAULT NULL, -- ; Window for Max User allowed per IP
`customer`.`rateLimitUserMaxRequest` INT DEFAULT NULL, -- ; Max request allowed for user
`customer`.`rateLimitUserMaxRequestWindow` INT DEFAULT NULL, -- ; Window for Max request allowed for user
`customer`.`rateLimitMaxUserLoginRequest` INT DEFAULT NULL, -- ; Max User Login request
`customer`.`rateLimitMaxUserLoginRequestWindow` INT DEFAULT NULL, -- ; Window for Max User Login request
```

## Customer/Group/User based Rate Limiting detail are set in respective Database Tables for records

```SQL
-- Customer level
`customer`.`rateLimitMaxRequest` int DEFAULT NULL,
`customer`.`rateLimitMaxRequestWindow` int DEFAULT NULL,

-- Group level
`group`.`rateLimitMaxRequest` int DEFAULT NULL,
`group`.`rateLimitMaxRequestWindow` int DEFAULT NULL,

-- User level
`user`.`rateLimitMaxRequest` int DEFAULT NULL,
`user`.`rateLimitMaxRequestWindow` int DEFAULT NULL,
```

## Rate Limiting at route level

If **enableRateLimitForRoute** is **enabled** the Rate Limiting settings indicates settings are present in SQL config file of the route. Each route can have different limits and windows or may also ignore (not compulsary for every route).

## Rate Limiting Key

```ini
rateLimitRoutePrefix='RRL:'   ; Route based Rate Limiting (RRL) Key prefix used in Redis
```

- SQL file configuration

```PHP
return [
	[...]
	'rateLimitMaxRequest' => 1,            // Allowed number of request
	'rateLimitMaxRequestWindow' => 3600,   // Window in Seconds for allowed number of request
	[...]
];
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
