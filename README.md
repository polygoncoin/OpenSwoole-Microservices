PHP low code API generator

This is a light & easy low code API generator using configuration arrays. It can be used to create API's in very short time once you are done with your database.

## Configuration Rules

- **[Rules For Custom DataTypes Configuration](Rules-For-Custom-DataTypes-Configuration.md)**
- **[Rules For Payload Formats](Rules-For-Payload-Formats.md)**
- **[Rules For Route Configuration](Rules-For-Route-Configuration.md)**
- **[Rules For Sql Configuration](Rules-For-SQL-Configuration.md)**
- **[Rules For TestCase Configuration](Rules-For-TestCase-Configuration.md)**

## JavaScript Examples

- **[JavaScript Examples](Microservices-JavaScript-Examples.md)**

## Sql File

- **Sql/global.sql** Import this Sql file on your **MySql global** instance
- **Sql/customer\_master.sql** Import this Sql file on your **MySql customer** instance

- **Note**: One can import both sql's in a single database to start with. Just configure the same detail in the environment files.

## folders

### Openswoole

- **openswoole_html** folder for Openswoole based application start files.

### File folder

- **Log** folder for application Log.
- **TestCase** folder for Test Cases

### Folder

- **Config** Basic configuration folder
- **Hook** Hook.
- **public\_html** Contains index.php file.
- **Supplement** Customised coding for APIs
- **Validation** Contains validation classes.

#### Supplement folder

- **Crons** Contains classes for cron API's
- **Custom** Contains classes for custom API's
- **Dropbox** Contains classes for third-party API's
- **LegacyCode** Contains classes for third-party API's
- **ThirdParty** Contains classes for third-party API's
- **Upload** Contains classes for upload file API's

### Route folder

#### Config//&lt;CustomerFoldername&gt;/&lt;mode&gt;/Route

- **/Config/&lt;CustomerFoldername&gt;/Private/Route/&lt;GroupName&gt;/DataModification**
- **/Config/&lt;CustomerFoldername&gt;/Private/Route/&lt;GroupName&gt;/DataRetrival**

- **/Config/&lt;CustomerFoldername&gt;/Public/Route//DataModification**
- **/Config/&lt;CustomerFoldername&gt;/Public/Route//DataRetrival**

- **&lt;GroupName&gt;** is the group user belongs to for accessing the API's

#### File

- **/GETroutes.php** for all GET method routes configuration.
- **/POSTroutes.php** for all POST method routes configuration.
- **/PUTroutes.php** for all PUT method routes configuration.
- **/PATCHroutes.php** for all PATCH method routes configuration.
- **/DELETEroutes.php** for all DELETE method routes configuration.

### Sql folder

These files locations are used in routes config to be used for generating response.

#### /Config/&lt;CustomerFoldername&gt;/Private/Sql

- **/Config//&lt;CustomerFoldername&gt;/&lt;mode&gt;/Sql/DataModification**
- **/Config//&lt;CustomerFoldername&gt;/&lt;mode&gt;/Sql/DataRetrival**

#### File

- **/GET/&lt;filenames&gt;.php** GET method Sql.
- **/POST/&lt;filenames&gt;;.php** POST method Sql.
- **/PUT/&lt;filenames&gt;.php** PUT method Sql.
- **/PATCH/&lt;filenames&gt;.php** PATCH method Sql.
- **/DELETE/&lt;filenames&gt;.php** DELETE method Sql.

One can replace **&lt;filenames&gt;** tag with desired name as per functionality.

## Setting route CIDR

Below are route level CIDR settings for a set of system routes (starting / ending with)

```SQL
`customer`.`customer_cron_cidr` VARCHAR(250) DEFAULT '0.0.0.0/0'
`customer`.`customer_custom_cidr` VARCHAR(250) DEFAULT '0.0.0.0/0'
`customer`.`customer_dropbox_cidr` VARCHAR(250) DEFAULT '0.0.0.0/0'
`customer`.`customer_explain_cidr` VARCHAR(250) DEFAULT '0.0.0.0/0'
`customer`.`customer_download_cidr` VARCHAR(250) DEFAULT '0.0.0.0/0'
`customer`.`customer_import_sample_cidr` VARCHAR(250) DEFAULT '0.0.0.0/0'
`customer`.`customer_import_cidr` VARCHAR(250) DEFAULT '0.0.0.0/0'
`customer`.`customer_routes_cidr` VARCHAR(250) DEFAULT '0.0.0.0/0'
`customer`.`customer_thirdparty_cidr` VARCHAR(250) DEFAULT '0.0.0.0/0'
`customer`.`customer_upload_cidr` VARCHAR(250) DEFAULT '0.0.0.0/0'
```

## Configuring in Database Tables

To enable CIDR settings at Customer / Group / User level one can set them in respective table and record

```SQL
-- Customer level
`customer`.`customer_cidr` VARCHAR(250) DEFAULT '0.0.0.0/0',

-- Group level
`group`.`customer_user_group_cidr` VARCHAR(250) DEFAULT '0.0.0.0/0',

-- User level
`user`.`customer_user_cidr` VARCHAR(250) DEFAULT '0.0.0.0/0',
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
