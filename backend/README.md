# Backend

Run all PHP and Composer commands in the container, for example:

```sh
docker compose run --rm backend composer install
docker compose up backend
docker compose run --rm backend composer test
docker compose run --rm backend vendor/bin/yii migrate/up
docker compose run --rm backend vendor/bin/yii migrate/down
```

The API listens on port 8080. MySQL is expected on the host, reachable as
`host.docker.internal`; configure it in the root `.env`. Never commit `.env`.
