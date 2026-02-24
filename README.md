# PHP low code API generator

This is a light & easy low code API generator using configuration arrays. It can be used to create API's in very short time once you are done with your database.

## .env Files

- **[.env.example](Env.example.md)**
- **[.env.enable.example](Env.enable.example.md)**
- **[.env.container.example](Env.container.example.md)**
- **[.env.cidr.example](Env.cidr.example.md)**
- **[.env.rateLimiting.example](Env.rateLimiting.example.md)**
- **[.env.session.example](Env.session.example.md)**

## SQL Files

- **Sql/global.sql** Import this SQL file on your **MySql global** instance
- **Sql/client\_master.sql** Import this SQL file on your **MySql client** instance

> **Note**: One can import both sql's in a single database to start with. Just configure the same details in the enviroment files.

## Folders

### Files Folder

- **Logs** Folder for application Logs.
- **Config** Basic configuration folder
- **Files** Folder for uploaded files.
- **Hooks** Hooks.
- **Supplement** Customised coding for APIs
- **Validation** Contains validation classes.

#### /Files Folder

- **Dropbox/Open** Uploaded files for open to web
- **Dropbox/Closed** Uploaded files by authorised users
- **ServingFiles/HTML** HTML files to be served with dynamic response (XSLT)
- **ServingFiles/PHP** PHP view files to be served with dynamic response
- **ServingFiles/XSLT** XSLT files to be served with dynamic response

#### /Supplement Folder

- **Crons** Contains classes for cron API's
- **Custom** Contains classes for custom API's
- **ThirdParty** Contains classes for third-party API's
- **Upload** Contains classes for upload file API's

### Routes Folder

#### /Config/Routes

- **/Config/Routes/Auth/&lt;GroupName&gt;**
- **/Config/Routes/Open**

**&lt;GroupName&gt;** is the group user belongs to for accessing the API's

#### Files

- **/GETroutes.php** for all GET method routes configuration.
- **/POSTroutes.php** for all POST method routes configuration.
- **/PUTroutes.php** for all PUT method routes configuration.
- **/PATCHroutes.php** for all PATCH method routes configuration.
- **/DELETEroutes.php** for all DELETE method routes configuration.

### Queries Folder

These files locations are used in routes config to be used for generating response.

#### /Config/Queries

- **/Config/Queries/Auth/GlobalDB** for global database.
- **/Config/Queries/Auth/ClientDB** for clients (including all hosts and their databases).
- **/Config/Queries/Open** for Open to Web API's (No Authentication).

#### Files

- **/GET/&lt;filenames&gt;.php** GET method SQL.
- **/POST/&lt;filenames&gt;;.php** POST method SQL.
- **/PUT/&lt;filenames&gt;.php** PUT method SQL.
- **/PATCH/&lt;filenames&gt;.php** PATCH method SQL.
- **/DELETE/&lt;filenames&gt;.php** DELETE method SQL.

> One can replace **&lt;filenames&gt;** tag with desired name as per functionality.

## 🤝 Contributing

Issues and feature requests are welcome.<br />
Feel free to share them on [issues page](https://github.com/polygoncoin/Openswoole-Microservices/issues)

## Author

👤 **Ramesh N Jangid (Sharma)**

- Github: [@polygoncoin](https://github.com/polygoncoin)

## 📝 License

Copyright © 2026 [Ramesh N Jangid (Sharma)](https://github.com/polygoncoin).<br />
This project is [MIT](License) licensed.
