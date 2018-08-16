# API REST CONSUMER
Simple Class to consume REST API with Guzzle library.

## Configuration
- Edit "config.php" file : login info
- Edit class/ApiCLient.php file : API URL + token managment (?)


## Features
- class : The main class to consume API (GET, POST, PUT)
- data/json : The body content template for request. Nomenclature : api_operation_name.mustache (operation = 'insert' or 'update')
- helpers
- logs
- (vendor) : composer

## Libraries
- **[Monolog](https://github.com/Seldaek/monolog)** : Logging for PHP
- **[Guzzle](https://github.com/guzzle/guzzle)** : PHP HTTP client
- **[Mustache](https://mustache.github.io/)** : Logic-less templates


