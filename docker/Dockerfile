# For build image production
FROM webdevops/php-nginx:8.0-alpine
ARG PRODUCTION=false
ARG WORKING_DIR='/var/www/html'
ARG AWS_ENV_PATH
ARG AWS_REGION=ap-southeast-1
RUN apk update && \
    apk add vim
RUN wget https://github.com/Droplr/aws-env/raw/master/bin/aws-env-linux-amd64 -O /usr/local/bin/aws-env && \
    chmod +x /usr/local/bin/aws-env
COPY ./docker/nginx /tmp/nginx
RUN if [ ${PRODUCTION} = true ]; then \
        cp /tmp/nginx/prod/static.conf /opt/docker/etc/nginx/vhost.common.d/; \
    else \
        cp /tmp/nginx/staging/static.conf /opt/docker/etc/nginx/vhost.common.d/; \
    fi; \
    rm /tmp/nginx -r
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV WEB_DOCUMENT_ROOT=${WORKING_DIR}
ENV AWS_ENV_PATH=${AWS_ENV_PATH}
ENV AWS_REGION=${AWS_REGION}
WORKDIR ${WORKING_DIR}
ADD . .
RUN composer install --no-scripts --no-dev --no-interaction
COPY ./docker/entrypoint.sh /opt/docker/provision/entrypoint.d/30-custom-entrypoint.sh
