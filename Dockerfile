FROM php:7.4.8-cli-alpine

RUN apk add --no-cache --virtual \
    coreutils \
    curl-dev \
    libcurl

RUN docker-php-ext-install curl

COPY entrypoint.sh /
COPY action.php /
WORKDIR /

RUN chmod +x /entrypoint.sh
RUN chmod +x /action.php

ENTRYPOINT ["/entrypoint.sh"]
