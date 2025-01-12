#!/bin/bash

set -ex
sleep 5 # Waiting for mysql being started.


##########################################################################
# Get App Files
##########################################################################
# If host machine has not files, give it files.
if [ ! -d "/app/app" ];then
    echo "Copying files from /app_src to /app"
    yes|cp -rf /app_src/. /app/
fi


##########################################################################
# Configuration
##########################################################################
function mod_env(){
    sed -i "s/^.\?$1\s\?=.*$/$1=${2//\//\\\/}/" $3
}

########### config php-fpm pool
php_fpm_config_file=/etc/php/8.1/fpm/pool.d/www.conf
# default php-fpm `pm` for server with 32GB max memory.
mod_env "pm"                   ${fpm_pm:-dynamic}                ${php_fpm_config_file}
mod_env "pm.max_children"      ${fpm_pm_max_children:-1024}      ${php_fpm_config_file}
# The following item is avaliable only if pm=dynamic.
mod_env "pm.start_servers"     ${fpm_pm_start_servers:-16}       ${php_fpm_config_file}
mod_env "pm.min_spare_servers" ${fpm_pm_min_spare_servers:-8}    ${php_fpm_config_file}
mod_env "pm.max_spare_servers" ${fpm_pm_max_spare_servers:-1024} ${php_fpm_config_file}
# php-fpm will be recreated after has processed for `pm.max_request` times.
mod_env "pm.max_requests"      ${fpm_pm_max_requests:-1000}      ${php_fpm_config_file}

########## config laravel env
mod_env "APP_DEBUG"         ${APP_DEBUG:-false}        .env
mod_env "HREF_FORCE_HTTPS"  ${HREF_FORCE_HTTPS:-false} .env
mod_env "TIMEZONE"          ${TZ:-Asia/Shanghai}       .env
mod_env "JUDGE_SERVER"      ${JUDGE_SERVER:-host.docker.internal} .env
mod_env "DB_HOST"           ${MYSQL_HOST:-host.docker.internal}   .env
mod_env "DB_PORT"           ${MYSQL_PORT:-3306} .env
mod_env "DB_DATABASE"       ${MYSQL_DATABASE}   .env
mod_env "DB_USERNAME"       ${MYSQL_USER}       .env
mod_env "DB_PASSWORD"       ${MYSQL_PASSWORD}   .env
mod_env "REDIS_HOST"        ${REDIS_HOST:-host.docker.internal} .env
mod_env "REDIS_PORT"        ${REDIS_PORT:-6379}                 .env
mod_env "REDIS_PASSWORD"    ${REDIS_PASSWORD:-null}             .env
mod_env "MAIL_MAILER"       ${MAIL_MAILER:-smtp}                .env
mod_env "MAIL_HOST"         ${MAIL_HOST:-smtp.qq.com}           .env
mod_env "MAIL_PORT"         ${MAIL_PORT:-465}                   .env
mod_env "MAIL_USERNAME"     ${MAIL_USERNAME:-null}              .env
mod_env "MAIL_PASSWORD"     ${MAIL_PASSWORD:-null}              .env
mod_env "MAIL_ENCRYPTION"   ${MAIL_ENCRYPTION:-ssl}             .env
mod_env "MAIL_FROM_ADDRESS" ${MAIL_FROM_ADDRESS:-null}          .env
mod_env "MAIL_FROM_NAME"    "\"${MAIL_FROM_NAME:-LDUOJ}\""      .env


##########################################################################
# Start Server
##########################################################################
# start nginx server
echo "Start at" $(date "+%Y-%m-%d %H:%M:%S") >> /app/storage/logs/nginx/access.log
echo "Start at" $(date "+%Y-%m-%d %H:%M:%S") >> /app/storage/logs/nginx/error.log
service nginx start

# Start php-fpm server
echo "Start at" $(date "+%Y-%m-%d %H:%M:%S") >> /app/storage/logs/php-fpm.log
service php8.1-fpm start


##########################################################################
# Initialize laravel app.
##########################################################################
php artisan storage:link
php artisan optimize
php artisan key:generate --force
php artisan migrate --force
php artisan optimize
php artisan lduoj:init

# Change project owner.
chown -R www-data:www-data bootstrap storage


##########################################################################
# Background running
##########################################################################
bash storage/logs/nginx/auto-clear-log.sh 2>&1 &


##########################################################################
# Start laravel-queue.
# Attention, One command `queue:work` only start one process.
# Although there are more than one queue their jobs are still executed one by one.
# TODO: Using supervisor to start more processes to run jobs of queues.
##########################################################################
php artisan queue:work --queue=default


##########################################################################
# Sleep forever to keep container alives.
##########################################################################
sleep infinity
