:: �Թ���Ա����
%1 mshta vbscript:CreateObject("Shell.Application").ShellExecute("cmd.exe","/c %~s0 ::","","runas",1)(window.close)&&exit

@shift /0
@echo off
title    ����/���� Zerotier ����
::color f0
mode con cols=38 lines=20
echo.&echo.&echo ==== ����/���� Zerotier ���� ====&echo.

set ztnic=ZeroTier One [8bd5124fd6171f26]
set ztip=10.10.168.3

for /f "tokens=2 delims= " %%i in ('echo^|ipconfig^|find "%ztnic%"')do set nic=%%i

if defined nic (
echo  ���� Zerotier NIC ...
netsh interface set interface "%ztnic%" disabled
) else (
echo  ���� Zerotier NIC,Please wait...
netsh interface set interface "%ztnic%" enabled
)
echo  All Done & pause>nul

