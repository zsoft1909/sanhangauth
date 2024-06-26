FROM webdevops/php-nginx-dev:8.0
ARG WORKING_DIR=/var/www/html
ENV WEB_DOCUMENT_ROOT=${WORKING_DIR}
RUN apt-get update
RUN apt-get install -y curl nodejs npm iputils-ping vim
# debian日本語・日本時間設定
# RUN apt-get install -y locales
# RUN sed -i -e 's/# ja_JP.UTF-8 UTF-8/ja_JP.UTF-8 UTF-8/' /etc/locale.gen
# RUN locale-gen
# ENV LANG ja_JP.UTF-8
# ENV LC_ALL ja_JP.UTF-8
# ENV LANGUAGE ja_JP:ja
# ENV TZ Asia/Tokyo
# COPY ./docker/horizon.conf /opt/docker/etc/supervisor.d
# COPY ./docker/queue.conf /opt/docker/etc/supervisor.d
# COPY ./docker/10-location-root.conf /opt/docker/etc/nginx/vhost.common.d
# COPY ./src/composer.json /home/
WORKDIR ${WORKING_DIR}
# de dam bao giu nguyen owner o folder duoc mount vao container, neu khong se anh huong den hoat dong deploy git pull