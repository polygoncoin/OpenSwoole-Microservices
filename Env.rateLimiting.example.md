# Rate Limiting

Rate Limiting is a method of restricting traffic for a set window of time. The time windows unit used here is No. of Seconds to allow a particular number of requests in respective param.

## More about Rate Limiting
To learn more about Rate Limiting one can [Check Google](https://www.google.com/search?q=Rate%20Limiting)

## 🤝 Globally Enable Rate Limiting

To enable Rate Limiting checks one can do this as below in .env.rateLimiting
```ini
enableRateLimiting=0                    ; 1 = true / 0 = false in
```

## 🤝 Configure Rate Limiting Server Details (Memcached / Redis)

```ini
; Supported Containers - Memcached / Redis without AUTH
rateLimitServerType='Memcached'         ; Redis/Memcached host dealing for Rate limit
rateLimitServerHostname='127.0.0.1'     ; Redis host dealing with Rate limit
rateLimitServerPort=11211               ; Redis-6379 / Memcached-11211
```

## 🤝 Enable Rate Limiting at respective function level

```ini
; 1 = true / 0 = false
enableRateLimitAtIpLevel=0              ; Function = IP
enableRateLimitAtClientLevel=0          ; Function = Client ID
enableRateLimitAtGroupLevel=0           ; Function = Group ID
enableRateLimitAtUserLevel=0            ; Function = User ID
enableRateLimitAtRouteLevel=0           ; Function = Configured Route
enableRateLimitAtUsersPerIpLevel=0      ; Function = IP & User ID
enableRateLimitAtUsersRequestLevel=0    ; Function = Requests & User ID
```

## 🤝 Setting Rate Limiting keys to be used as identifier with Function(s) combined

```ini
; Rate limit open traffic (not limited by allowed IPs/CIDR and allowed Rate Limits to users)
rateLimitIPPrefix='IPRL:'
; Client based Rate Limitng (GRL) key prefix used in Redis
rateLimitClientPrefix='CRL:'
; Group based Rate Limitng (GRL) key prefix used in Redis
rateLimitGroupPrefix='GRL:'
; User based Rate Limitng (URL) key prefix used in Redis
rateLimitUserPrefix='URL:'
; Route based Rate Limiting (RRL) key prefix used in Redis
rateLimitRoutePrefix='RRL:'
; User Per IP based Rate Limiting (UIRL) key prefix used in Redis
rateLimitUsersPerIpPrefix='UIRL:'
; User Per IP based Rate Limiting (UIRL) key prefix used in Redis
rateLimitUsersRequestPrefix='URRL:'
```

## 🤝 Setting Rate Limiting keys Limits with window in seconds

```ini
; Rate Limiting No. of Requests per IP ('IPRL:')
rateLimitIPMaxRequests=600              ; Max request allowed per IP
rateLimitIPMaxRequestsWindow=300        ; Window in seconds of Max request allowed per IP

; Rate Limiting No. of User Per IP ('UIRL:')
rateLimitUsersPerIpMaxUsers=10          ; Max Users allowed per IP
rateLimitUsersPerIpMaxUsersWindow=300   ; Window in seconds of Max Users allowed per IP

; Rate Limiting No. of Requests per User ('URRL:')
; Delay Between Consecutive Requests (allow n requests only for seconds configured for each user)
rateLimitUsersMaxRequests=1             ; Max one request allowed for 10 seconds
rateLimitUsersMaxRequestsWindow=10      ; Max one request allowed for 10 seconds
```

## Client/Group/User based Rate Limiting details are set in respective DB Tables for records

```SQL
# Client level
`clients`.`rateLimitMaxRequests` int DEFAULT NULL,
`clients`.`rateLimitMaxRequestsWindow` int DEFAULT NULL,

# Group level
`groups`.`rateLimitMaxRequests` int DEFAULT NULL,
`groups`.`rateLimitMaxRequestsWindow` int DEFAULT NULL,

# User level
`users`.`rateLimitMaxRequests` int DEFAULT NULL,
`users`.`rateLimitMaxRequestsWindow` int DEFAULT NULL,
```

## Rate Limiting at route level

If **enableRateLimitAtRouteLevel** is **enabled** the Rate Limiting settings indicates settings are present in SQL config file of the route. Each route can have different limits and windows or may also ignore (not compulsary for every route).

- Rate Limiting Key

```ini
rateLimitRoutePrefix='RRL:'   ; Route based Rate Limiting (RRL) key prefix used in Redis
```

- SQL file configuration

```PHP
return [
    [...]
    'rateLimitMaxRequests' => 1,            // Allowed number of requests
    'rateLimitMaxRequestsWindow' => 3600,   // Window in Seconds for allowed number of requests
    [...]
];
```

## 🤝 Contributing

Issues and feature requests are welcome.<br />
Feel free to share them on [issues page](https://github.com/polygoncoin/Openswoole-Microservices/issues)

## Author

👤 **Ramesh N Jangid (Sharma)**

- Github: [@polygoncoin](https://github.com/polygoncoin)

## 📝 License

Copyright © 2026 [Ramesh N Jangid (Sharma)](https://github.com/polygoncoin).<br />
This project is [MIT](License) licensed.
