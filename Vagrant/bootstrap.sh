#!/usr/bin/env bash

# Vagrant bootstrap file for php-inventory

# Initial package installation

sudo apt-get update

if [ ! -f /var/log/swsetup ];
then
	sudo debconf-set-selections <<< 'mysql-server-5.5 mysql-server/root_password password f0a8266bb2930e6b'
	sudo debconf-set-selections <<< 'mysql-server-5.5 mysql-server/root_password_again password f0a8266bb2930e6b'
	
	sudo apt-get install -y mysql-server-5.5 php5-mysql apache2 php5 imagemagick php5-imagick zip
	if ! [ -L /var/www ]; then
		rm -rf /var/www
		ln -fs /vagrant /var/www
	fi
	
	apt-get -f install
	chown -R vagrant.vagrant /var/lock/apache2
	sudo sed -i "s|\("^export\ APACHE_RUN_USER=" * *\).*|\1vagrant|" /etc/apache2/envvars
	sudo sed -i "s|\("^export\ APACHE_RUN_GROUP=" * *\).*|\1vagrant|" /etc/apache2/envvars
	
	sudo service apache2 stop
	sudo service apache2 start
	touch /var/log/swsetup
fi

# Initial database configuration

if [ ! -f /var/log/dbsetup ];
then
	echo "CREATE USER 'vagrant'@'localhost' IDENTIFIED BY 'vagrant'" | mysql -uroot -pf0a8266bb2930e6b
	echo "CREATE DATABASE inventory" | mysql -uroot -pf0a8266bb2930e6b
	echo "GRANT ALL ON inventory.* TO 'vagrant'@'localhost'" | mysql -uroot -pf0a8266bb2930e6b
	echo "flush privileges" | mysql -uroot -pf0a8266bb2930e6b

	if [ -f /vagrant/tables.sql ];
	then
		mysql -uroot -pf0a8266bb2930e6b inventory < /vagrant/tables.sql
		# Insert default location and group into the databases
		echo "INSERT INTO locations (shortName) VALUES (\"Default\");" | mysql -uroot -pf0a8266bb2930e6b inventory
		echo "INSERT INTO groups (shortName) VALUES (\"Default\");" | mysql -uroot -pf0a8266bb2930e6b inventory
	fi

	touch /var/log/dbsetup
fi
