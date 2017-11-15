# WHMCS OpenVZ 6
Module for OpenVZ 6 integration

## Requirements
1. WHMCS 6.3 or greater (https://www.whmcs.com/)
2. libssh2 (https://www.libssh2.org/)

### Installing SSH2 with PECL
```
yum install gcc make libssh2 libssh2-devel && \
pecl channel-update pecl.php.net && \
pecl install ssh2
```

Add to php.ini
```
extension = "ssh2.so"
```

Then restart Apache
```
service httpd restart
```

Check this
```
php -m | grep ssh2
```

## Module installation
```
cd PATH_TO_WHMCS/modules/servers && \
git clone https://github.com/thalidzhokov/whmcs-openvz6 openvz6
```

OR just upload files into your WHMCS folder _/modules/servers/openvz6/_

