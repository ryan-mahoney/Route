docker run \
    -e "OPINE_ENV=docker" \
    --rm \
    -v "$(pwd)/../":/app opine:phpunit-route \
    --bootstrap /app/tests/bootstrap.php
