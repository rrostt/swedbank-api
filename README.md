## Setup

1. Run composer

``docker run --rm -v $(pwd):/app composer/composer install``

2. Build docker image

``docker build -t swedbank-api .``

3. Run container

``docker run -p 80:80 -d swedbank-api``
