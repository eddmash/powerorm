#!/usr/bin/env bash

sudo mysql -e "use mysql; update user set authentication_string=PASSWORD('root') where user='root'"
sudo mysql -e "use mysql;update user set plugin='mysql_native_password'"
sudo mysql -e "use mysql;FLUSH PRIVILEGES;"
sudo mysql_upgrade -u root -proot
sudo mysql -u root -proot -e "create database powerormtest;"
sudo service mysql restart