
:: 在 windows7 以上添加he.net的v6后，可能还需添加v6路由才能使用 v6 访问网络  
:: 在ipv6.he.net 上查看route和网关，大概是这样 2001:470:69:1a2::/64 2001:470:68:1a2::1  
:: 查看本机分配的v6地址，批处理可以是  
:: for /f "tokens=16 delims= " %%i in ('echo^|ipconfig^|find "2001"')do set ipv6=%%i  
  
:: 在开始菜单搜索CMD，以管理员运行CMD  
:: 输入以下其中的一条命令  
  
route -6 add 2001:470:67:de2::/64 2001:470:66:de2::1  
route add ::/0 2001:470:66:de2::4  
  
:: route -6 print  
  
  
