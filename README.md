# Simple Swedbank Sweden Web API

An API wrapper for @walle89's [SwedbankJson](https://github.com/walle89/SwedbankJson). Since SwedbankJson was built in PHP I wrote this wrapper so I can use it from e.g. node.js applications.

Easily integrated into projects as a docker container.

## API endpoints

API calls are made on the form `/<action>?arguments`.

### Mobile BankID

The API authenticates using Mobile BankID. When making a request the API starts the authentication process and waits up to 30 second for the user to launch the BankID app and auhenticate. It is up the user of the API to notify the enduser what they have to do.

### /accounts

``GET /accounts?username=19830225xxxx``

This will fetch the available accounts on the default profile of the authenticated user.

### /transfer

``POST /transfer?username=19830225xxx?from=<ACCOUNT NUMBER~&to=<ACCOUNT NUMBER>&amount=10&fromMsg=money&toMsg=gift``

Will make a transfer between two accounts. The account numbers are on the form identical to fullyFormattedNumber as given by the Swedbank API, e.g. "8327-9,924 547 425-3". We cannot use account IDs given by the Swedbank API as it will return session unique IDs each time the user
authenticates with the server.

fromMsg and toMsg are optional. If fromMsg is specified but not toMsg, toMsg will be set to fromMsg.


## Setup

1. Run composer

``docker run --rm -v $(pwd):/app composer/composer install``

2. Build docker image

``docker build -t swedbank-api .``

3. Run container

``docker run -p 80:80 -d swedbank-api``
