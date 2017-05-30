# WHMCS OpenVZ 6
Module for OpenVZ 6 integration

## Requirements
1. WHMCS 6.3 or greater (https://www.whmcs.com/)
2. libssh2 (https://www.libssh2.org/)

### Installing SSH2 with PECL
_yum install gcc make libssh2 libssh2-devel_ \
_pecl channel-update pecl.php.net_ \
_pecl install ssh2_

Add to php.ini \
_extension = "ssh2.so"_

Then restart Apache \
_service httpd restart_

Check this \
_php -m | grep ssh2_

## Module installation
1. Just upload _/modules/servers/openvz6/_ in your WHMCS folder

