# Configure Apache as reverse proxy #

The code below has the steps about how to install/configure Apache as a reverse proxy to a container service 

```sh
apt-get install -y aptitude
aptitude -y upgrade
aptitude install -y build-essential
aptitude install -y apache2 libxml2-dev
a2enmod proxy proxy_ajp proxy_http rewrite deflate headers proxy_balancer proxy_connect proxy_html
service apache2 restart
```
Edit the  /etc/apache2/sites-enabled/000-default.conf  to that you have, at the end, this content:

```sh
nano /etc/apache2/sites-enabled/000-default.conf
```

```conf
<VirtualHost *:80>
    ServerAdmin webmaster@drypack.com
	  ServerName drypack.com
	  ServerAlias www.drypack.com

    ProxyPreserveHost On

    # Servers to proxy the connection, or;
    # List of application servers:
    # Usage:
    # ProxyPass / http://[IP Addr.]:[port]/
    # ProxyPassReverse / http://[IP Addr.]:[port]/
    # Example:
    ProxyPass / http://0.0.0.0:8081/
    ProxyPassReverse / http://0.0.0.0:8081/

    ServerName localhost
</VirtualHost>
```
