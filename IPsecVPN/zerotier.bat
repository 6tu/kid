:: 以管理员运行
%1 mshta vbscript:CreateObject("Shell.Application").ShellExecute("cmd.exe","/c %~s0 ::","","runas",1)(window.close)&&exit

@shift /0
@echo off
title    禁用/启动 Zerotier 网卡
::color f0
mode con cols=38 lines=20
echo.&echo.&echo ==== 禁用/启用 Zerotier 网卡 ====&echo.

set ztnic=ZeroTier One [8bd5124fd6171f26]
set ztip=10.10.168.3

for /f "tokens=2 delims= " %%i in ('echo^|ipconfig^|find "%ztnic%"')do set nic=%%i

if defined nic (
echo  禁用 Zerotier NIC ...
netsh interface set interface "%ztnic%" disabled
) else (
echo  启用 Zerotier NIC,Please wait...
netsh interface set interface "%ztnic%" enabled
)
echo  All Done & pause>nul

