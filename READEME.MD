### Development

To use a specific Compose file and `.env.local` for development, use the following command:

```bash
docker compose -f docker-compose.dev.yaml --env-file .env.local up --build -d
```

To set up Xdebug for development, use the following command:

```bash
docker compose -f docker-compose.dev.yaml -f docker-compose.xdebug.yaml --env-file .env.local up -d --build
```

### Production

To build the production image, use the following command:

```bash
docker build --target app_prod -t tomsterauds/php-composer:1.0 -f ./php/Dockerfile .
```

To push the production image to Docker Hub, use the following command:

```bash
docker push tomsterauds/php-composer:1.0
```

### Running Tests

To run the tests, use the following command:

```bash
./vendor/bin/phpunit
```

### PHP

To open a shell in the running PHP container, use the following command:

```bash
docker compose exec app sh
```

