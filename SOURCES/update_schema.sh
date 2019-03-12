#/bin/bash
#
# install or update SQL schema for rgmweb
#
# Eric Belhomme <ebelhomme@fr.scc.com>

PATH="/sbin:/usr/sbin:/bin:/usr/bin"
RGMWEBVARLIB="/var/lib/rgmweb"
RGMWEBDBNAME="rgmweb"

MYSQL_SOCKET=$(mktemp -u --suffix -mysql-sock)
MYSQL="/usr/bin/mysql -u root --socket=${MYSQL_SOCKET} --batch --silent --skip-column-names"

# stop mariadb daemon then restarts it with no network and a random socket
# so we ensure nothing can connect while it runs in safe mode with no authentication
systemctl stop mariadb
/usr/bin/mysqld_safe --skip-grant-tables --no-auto-restart --skip-networking --socket=${MYSQL_SOCKET} &
MYSQL_PID=$(ps aux | grep '^mysql .*[m]ysqld' | awk '{print $2}')

if [ "$($MYSQL -e 'show databases' | grep -c '^rgmweb')" == "0" ]; then
    $MYSQL -e "CREATE DATABASE ${RGMWEBDBNAME};"
    cat ${RGMWEBVARLIB}/sql/schema.sql | $MYSQL ${RGMWEBDBNAME}
fi

kill -15 $MYSQL_PID
systemctl start mariadb