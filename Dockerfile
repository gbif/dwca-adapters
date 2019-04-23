FROM php:5.4-cli
RUN apt-get update || echo "Expected to fail, as this is an old, unsupported Debian."
RUN apt-get install -y zlib1g-dev
RUN docker-php-ext-install zip

COPY . /dwca-adapters

WORKDIR /dwca-adapters
VOLUME /dwca-adapters/output

CMD [ "/usr/local/bin/php", "index.php", "all" ]
