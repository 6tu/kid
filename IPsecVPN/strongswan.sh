
# =====> 安装包 可安装GCC
if [ ! -f "/usr/bin/yum" ]; then
    apt -y update
    apt -y install wget curl dos2unix zip unzip ca-certificates rename make
    apt -y install openssl libssl-dev strongswan libstrongswan
    apt -y install libcharon-extra-plugins libstrongswan-extra-plugins
    apt -y install iptables
    apt -y install iptables-persistent
    strongswan_path = /etc/
else
    yum -y install epel-release
    yum -y update
    yum -y install wget curl dos2unix zip unzip ca-certificates rename make
    yum -y install openssl-devel strongswan
    yum -y install iptables-services
    sed -i 's/SELINUX=enforcing/SELINUX=disabled/' /etc/selinux/config
    strongswan_path = /etc/strongswan/
fi
wget https://www.rarlab.com/rar/rarlinux-x64-5.9.1.tar.gz
tar -xzf rarlinux-x64-5.9.1.tar.gz
cd rar;make;cd ..
rm -rf rar
wget -O -  https://get.acme.sh | sh
sed -i '/#Port 22/a\Port 2222\n' /etc/ssh/sshd_config
chmod 0666 /etc/hosts*

chkconfig --add strongswan
systemctl enable strongswan
systemctl enable ipsec

ipsec  restart
strongswan restart

# =====> 安装证书
wget https://raw.githubusercontent.com/6tu/pub/master/certs/certs-init.sh
wget https://raw.githubusercontent.com/6tu/pub/master/certs/makecert.sh
wget https://raw.githubusercontent.com/6tu/pub/master/certs/acmecert.sh
wget https://raw.githubusercontent.com/6tu/code/master/linux/vpn/proxyndp.updown

chmod +x strongswan proxyndp.updown *.sh
dos2unix strongswan proxyndp.updown *.sh

bash ./certs-init.sh
bash ./makecert.sh
test -d $strongswan_path/ipsec.d/cacerts  || mkdir -p $strongswan_path/ipsec.d/cacerts
test -d $strongswan_path/ipsec.d/certs    || mkdir -p $strongswan_path/ipsec.d/certs
test -d $strongswan_path/ipsec.d/private  || mkdir -p $strongswan_path/ipsec.d/private

/bin/cp -rf  ~/certs/*_cert.crt        $strongswan_path/ipsec.d/certs/server.cert.pem
/bin/cp -rf  ~/certs/*_csr_nopw.key    $strongswan_path/ipsec.d/private/server.pem
/bin/cp -rf  ~/certs/demoCA/cacert.pem $strongswan_path/ipsec.d/cacerts/ca.cert.pem

/bin/cp -rf  proxyndp.updown $strongswan_path/strongswan.d/

# =====> 设置防火墙
iptables -t nat -F
iptables -t nat -X
iptables -t nat -Z
iptables -t nat -P PREROUTING ACCEPT
iptables -t nat -P POSTROUTING ACCEPT
iptables -t nat -P OUTPUT ACCEPT
iptables -t mangle -F
iptables -t mangle -X
iptables -t mangle -P PREROUTING ACCEPT
iptables -t mangle -P INPUT ACCEPT
iptables -t mangle -P FORWARD ACCEPT
iptables -t mangle -P OUTPUT ACCEPT
iptables -t mangle -P POSTROUTING ACCEPT
iptables -F
iptables -X
iptables -P FORWARD ACCEPT
iptables -P INPUT ACCEPT
iptables -P OUTPUT ACCEPT
iptables -t raw -F
iptables -t raw -X
iptables -t raw -P PREROUTING ACCEPT
iptables -t raw -P OUTPUT ACCEPT

iptables -t nat -A POSTROUTING -s 10.10.2.0/24 -o eth0 -j MASQUERADE

invoke-rc.d iptables-persistent save
netfilter-persistent save
iptables-save
iptables-save > /etc/iptables/rules.v4
ip6tables-save > /etc/iptables/rules.v6
invoke-rc.d iptables restart


systemctl enable iptables
systemctl start iptables
service iptables save
service iptables restart


# =====> 数据转发
echo net.ipv4.ip_forward=1               >> /etc/sysctl.conf 
echo net.ipv6.conf.all.forwarding=1      >> /etc/sysctl.conf 
echo net.ipv6.conf.all.proxy_ndp=1       >> /etc/sysctl.conf 
echo "net.core.default_qdisc=fq"         >> /etc/sysctl.conf
echo "net.ipv4.tcp_congestion_control=bbr" >> /etc/sysctl.conf

sysctl -p


# =====> 增加swap
swapoff /var/swap
dd if=/dev/zero of=/var/swap bs=512 count=1048576
/sbin/mkswap -f /var/swap
/sbin/swapon -f /var/swap
swapon -s
echo "/var/swap swap swap defaults 0 0" >> /etc/fstab


