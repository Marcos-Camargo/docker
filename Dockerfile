FROM php:7.2.24-fpm

RUN mkdir -p /etc/nginx/conf.d

RUN echo 'deb http://httpredir.debian.org/debian jessie contrib' >> /etc/apt/sources.list

RUN apt-get update
RUN export DEBIAN_FRONTEND=noninteractive && apt-get install -y --force-yes libssl-dev curl  libcurl4-gnutls-dev libxml2-dev libicu-dev libmcrypt4 libmemcached11 openssl

#CONFIGURAÇÕES DO OPCACHE
RUN docker-php-ext-install opcache

#CONFIGURAÇÕES DO APCU
RUN pecl install apcu-5.1.5 && docker-php-ext-enable apcu

#LIBS EXTRAS
RUN docker-php-ext-install bcmath
RUN apt-get install -y libbz2-dev
RUN docker-php-ext-install bz2
RUN docker-php-ext-install mbstring
RUN apt-get install -y libpq-dev
RUN apt-get install -y libicu-dev
RUN docker-php-ext-install intl

#GD
RUN apt-get install -y libfreetype6-dev libjpeg62-turbo-dev libpng16-16
RUN docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/
RUN docker-php-ext-install gd

#PDO - CUSTOMIZAR A SEU DISPOR
RUN docker-php-ext-install pdo_mysql

RUN docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable pdo_mysql

RUN apt-get install tree
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug
# Configuração para passar os gruoup by
# SET GLOBAL sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));

ARG WITH_XDEBUG=true
RUN apt-get install ssh nano -y
RUN dpkg --add-architecture i386
# RUN apt-get update -y
# RUN apt-get upgrade -y
# RUN apt-get install -y git busild-essential make gcc vim net-tools iputils-ping ca-certificates openssh-server libc6:i386 libstdc++6:i386
RUN echo "root:root" | chpasswd
RUN sed -i 's/prohibit-password/yes/' /etc/ssh/sshd_config
RUN cat /etc/ssh/sshd_config
RUN mkdir /root/.ssh
RUN chown -R root:root /root/.ssh;chmod -R 700 /root/.ssh
RUN echo “StrictHostKeyChecking=no” >> /etc/ssh/ssh_config
RUN service ssh restart
EXPOSE 22
EXPOSE 80
WORKDIR /public
