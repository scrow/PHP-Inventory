#!/usr/bin/env bash

# Vagrant bootstrap file for php-inventory

# Initial package installation

sudo apt-get update

if [ ! -f /var/log/swsetup ];
then
  sudo debconf-set-selections <<< 'mysql-server-5.5 mysql-server/root_password password f0a8266bb2930e6b'
  sudo debconf-set-selections <<< 'mysql-server-5.5 mysql-server/root_password_again password f0a8266bb2930e6b'
  
  sudo apt-get install -y mysql-server-5.5 php5-mysql apache2 php5 imagemagick php5-imagick
  if ! [ -L /var/www ]; then
    rm -rf /var/www
    ln -fs /vagrant /var/www
  fi
  
  apt-get -f install

  apachectl restart

  touch /var/log/swsetup
fi

# Initial database configuration

if [ ! -f /var/log/dbsetup ];
then
  echo "CREATE USER 'vagrant'@'localhost' IDENTIFIED BY 'vagrant'" | mysql -uroot -pf0a8266bb2930e6b
  echo "CREATE DATABASE inventory" | mysql -uroot -pf0a8266bb2930e6b
  echo "GRANT ALL ON inventory.* TO 'vagrant'@'localhost'" | mysql -uroot -pf0a8266bb2930e6b
  echo "flush privileges" | mysql -uroot -pf0a8266bb2930e6b

  touch /var/log/dbsetup

  if [ -f /vagrant/tables.sql ];
  then
    mysql -uroot -pf0a8266bb2930e6b inventory < /vagrant/tables.sql
  fi
fi
