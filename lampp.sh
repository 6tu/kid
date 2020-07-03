#!/usr/bin/env bash
PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin:~/bin
export PATH

webroot=/var/www
time=`date +%Y%m%d%H%M%S`
freemem=`free -m|awk 'NR==2 {print $NF}'`
sysbit=`getconf LONG_BIT`

if [ ! -f "/usr/bin/yum" ]; then
  aptyum=apt-get
  apt-get install -y git wget zip unzip ca-certificates rename
  apt-get -y autoremove apache2
  apt-get -y autoremove nginx
else
  aptyum=yum
  yum install -y git wget zip unzip ca-certificates rename
  yum -y remove apache2 nginx
fi

# 32位系统或者内存小于0.5G时不能安装lampp，采用从源安装Apache+PHP5
echo "" && echo "======== install web Server ========" && echo ""
echo "运行环境: 64位操作系统，内存不小于0.5 G" && echo ""
if [ $freemem -lt 500 ];then
  echo "内存小于0.5G,不能安装 XAMPP" && echo ""
  echo "Apache and PHP5 is installing"
  ${aptyum} install -y apache2 php5 libapache2-mod-php5
  exit 1
fi

if [ 32 == $sysbit ];then
  echo "32位操作系统,不能安装 XAMPP" && echo ""
  echo "Apache and PHP5 is installing"
  ${aptyum} install -y apache2 php5 libapache2-mod-php5
  exit 1
fi

# 下载 xampp
cd ~
basepath=$(cd `dirname $0`; pwd)
cd $basepath
test -d $basepath/soft || mkdir -p $basepath/soft
cd $basepath/soft
# wget http://soft.vpser.net/lnmp/lnmp1.4-full.tar.gz
# wget --no-check-certificate --content-disposition https://shideyun.com/xampp.php?os=linux -o log
# find . -name "*.run?from_af=true" | sed 's/\.run?from_af=true$//g' | xargs -I{} mv {}.run?from_af=true {}.run
## rename "s/\?from_af=t//" *
## rename "s/runrue/run/" *
# xampp_latest=`cat log  | sed -ne "s/Saving to: ‘\(.*\)’/\1/p" | sed 's/\?from_af=true$//g'`
# rm -rf log

wget --no-check-certificate -q https://shideyun.com/xampp.php
xampp_url=`grep -o "http.*\.run" xampp.php`
OLD_IFS="$IFS"
IFS="/"
arr=($xampp_url)
IFS="$OLD_IFS"
xampp_latest=${arr[5]}
xampp_specver=xampp-linux-x64-7.2.4-0-installer.run
echo "" && echo "将要下载以下文件"
echo ${xampp_latest} && echo ${xampp_specver} && echo ""
wget --no-check-certificate -nv ${xampp_url}
wget --no-check-certificate -nv https://www.apachefriends.org/xampp-files/7.2.4/${xampp_specver}
chmod +x xampp*
rm -rf xampp.php


# 查看glibc版本
# glibc版本 https://blog.csdn.net/xiaoxinyu316/article/details/44834255
# Linux查看glibc版本信息
# 
# centos：
# rpm -qa | grep glibc
# rpm -qi glibc
# ls -l /lib/libc.so.6
# 
# ubuntu：
# ls -l /lib/i386-linux-gnu/libc.so.6
# apt-cache show libc6
# 
# 发行版无关方法：
# ldd –version

# 根据 glibc 版本安装 xampp
glibc=`ldd --version | sed -ne "s/ldd (GNU libc) \(.*\)/\1/p"`
if [ `expr $glibc \> 2.13` -eq 0 ];then
  echo "将安装 ${xampp_specver}"
  $basepath/soft/${xampp_specver}
else
  echo "将安装 ${xampp_latest}"
  $basepath/soft/${xampp_latest}
fi

sed -i 's/if egrep "9 "/if egrep "Red "/g' /opt/lampp/lampp

# 修改配置 WEB目录
sed -i "s/\/opt\/lampp\/htdocs/\/var\/www/g" /opt/lampp/etc/httpd.conf
sed -i "s/\/opt\/lampp\/cgi-bin/\/var\/cgi-bin/g" /opt/lampp/etc/httpd.conf
sed -i "s/\/opt\/lampp\/htdocs/\/var\/www/g" /opt/lampp/etc/extra/httpd-ssl.conf
sed -i "s/\/opt\/lampp\/cgi-bin/\/var\/cgi-bin/g" /opt/lampp/etc/extra/httpd-ssl.conf
sed -i "s/Require local/# Require local \n    Require all granted/g" /opt/lampp/etc/extra/httpd-xampp.conf
sed -i "s/\['auth_type'] = 'config';/\['auth_type'] = 'config';\n\n\$cfg['Servers'][\$i]['auth_type'] = 'cookie';\n#/g" /opt/lampp/phpmyadmin/config.inc.php

/bin/cp -rf /opt/lampp/lampp /etc/init.d/
/bin/cp -rf /opt/lampp/lampp /opt/lamp
chmod +x /etc/init.d/lampp
update-rc.d lampp defaults
chkconfig --add lampp
chkconfig lampp on

/opt/lampp/ctlscript.sh restart apache

# 设定 mysql 密码和 phpmyadmin
mysqlpw=`openssl rand -base64 8`
echo ${mysqlpw} > /opt/lampp/mysqlpw
/opt/lampp/ctlscript.sh restart mysql
/opt/lampp/bin/mysqladmin --user=root password ${mysqlpw}

# 增加 FTP 分组
groupadd ftp
useradd -g ftp -d /dev/null -s /usr/sbin/nologin ftp
test -d /var/pub || mkdir -p /var/pub
ftppub="/var/pub"
chown ftp:ftp /var/pub
chmod 0777 /var/pub
useradd ftp -g ftp -m -d ${ftppub} -s /sbin/nologin





# 建立 WEB 目录及相关文件
test -d ${webroot}/tz || mkdir -p ${webroot}/tz
cd ${webroot}
echo 'hello world!'>${webroot}/index.html
echo '<?php phpinfo();' > ${webroot}/tz/phpinfo.php
wget -q --no-check-certificate https://raw.githubusercontent.com/6tu/pub/master/php/tz.zip
wget -q --no-check-certificate https://raw.githubusercontent.com/6tu/pub/master/php/hosts.zip
wget -q --no-check-certificate https://github.com/kalcaddle/KodExplorer/archive/master.zip
unzip -o -q -d ./ tz.zip
unzip -o -q -d ./ hosts.zip
unzip -o -q -d ./ master.zip
mv KodExplorer-master kod
rm -rf master.zip

/bin/cp -rf /opt/lampp/cgi-bin /var
chmod -R 0755 /var/cgi-bin
chown -R daemon:daemon /var/cgi-bin
chown -R daemon:daemon ${webroot}
chmod 0777 ${webroot}
chmod 0644 -R ${webroot}
find ${webroot} -type d -exec chmod 0755 {} \;
find ${webroot}/* -exec touch {} \; 





