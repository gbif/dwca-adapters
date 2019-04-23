FROM php:5.4-cli
RUN apt-get update || echo "Expected to fail, as this is an old, unsupported Debian."
RUN apt-get install -y zlib1g-dev
RUN docker-php-ext-install zip

# FixUID: https://github.com/boxboat/fixuid
RUN addgroup --gid 1000 dwca && adduser --uid 1000 --ingroup dwca --home /dwca-adapters --shell /bin/sh --disabled-password --gecos "" dwca
RUN USER=dwca && \
    GROUP=dwca && \
    curl -SsL https://github.com/boxboat/fixuid/releases/download/v0.4/fixuid-0.4-linux-amd64.tar.gz | tar -C /usr/local/bin -xzf - && \
    chown root:root /usr/local/bin/fixuid && \
    chmod 4755 /usr/local/bin/fixuid && \
    mkdir -p /etc/fixuid && \
    printf "user: $USER\ngroup: $GROUP\n" > /etc/fixuid/config.yml
ENTRYPOINT ["fixuid", "-q"]

COPY . /dwca-adapters
RUN chown dwca:dwca -R /dwca-adapters

WORKDIR /dwca-adapters
VOLUME /dwca-adapters/output

USER dwca:dwca
CMD [ "/usr/local/bin/php", "index.php", "all" ]
