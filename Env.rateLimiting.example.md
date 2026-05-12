# Rate Limiting

Rate Limiting is a method of restricting traffic for a set window of time. The time windows unit used here is No. of Seconds to allow a particular number of request in respective param.

## More about Rate Limiting
To learn more about Rate Limiting one can [Check Google](https://www.google.com/search?q=Rate%20Limiting)

## Globally Enable Rate Limiting

To enable Rate Limiting checks one can do this as below in .env.rateLimiting
```ini
enableRateLimiting=0                    ; 1 = true / 0 = false in
```

## Enable Rate Limiting at respective function level

```ini
; 1 = true / 0 = false
enableRateLimitForIp=0              ; Function = IP
enableRateLimitForCustomer=0          ; Function = Customer id
enableRateLimitForGroup=0           ; Function = Group id
enableRateLimitForUser=0            ; Function = User id
enableRateLimitForRoute=0           ; Function = Configured Route
enableRateLimitForUserPerIp=0      ; Function = IP & User id
enableRateLimitForUserRequest=0    ; Function = request & User id
```

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

```ini
; Rate Limiting No. of request per IP ('IPRL:')
rateLimitIPMaxRequest=600              ; Max request allowed per IP
rateLimitIPMaxRequestWindow=300        ; Window in seconds of Max request allowed per IP

; Rate Limiting No. of User Per IP ('UIRL:')
rateLimitMaxUserPerIp=10          ; Max User allowed per IP
rateLimitMaxUserPerIpWindow=300   ; Window in seconds of Max User allowed per IP

; Rate Limiting No. of request per User ('URRL:')
; Delay Between Consecutive request (allow n request only for seconds configured for each user)
rateLimitUserMaxRequest=1             ; Max one request allowed for 10 seconds
rateLimitUserMaxRequestWindow=10      ; Max one request allowed for 10 seconds
```

## Customer/Group/User based Rate Limiting detail are set in respective DB Tables for records

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
Feel free to share them on [issues page](https://github.com/polygoncoin/OpenSwoole-Microservices/issues)

## Author

- **Ramesh N. Jangid (Sharma)**

Github: [@polygoncoin](https://github.com/polygoncoin)

## License

Copyright © 2026 [Ramesh N. Jangid (Sharma)](https://github.com/polygoncoin).<br />
This project is [MIT](License) licensed.
