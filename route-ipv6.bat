:: �Թ���Ա����
%1 mshta vbscript:CreateObject("Shell.Application").ShellExecute("cmd.exe","/c %~s0 ::","","runas",1)(window.close)&&exit

@shift /0
@echo off
title    ��� IPv6 ·��
::color f0
mode con cols=38 lines=20
echo.&echo.&echo ======== ��� IPv6 ·�� ========&echo.
set /p host=VPNname:
setlocal enabledelayedexpansion
for /f "tokens=1,2 delims=" %%i in ('netsh interface ipv6 show address %host%') do (
        set /a n+=1
        if !n!==1 set var=%%i
)
:: ��ǰIPv6������ %var% 
for /f "tokens=1,2 delims= " %%i in ('echo "%var%"') do set ipv6=%%j

:: ����������Ҳ���ԣ�������ܱȽ�׼ȷ
:: for /f "tokens=16 delims= " %%i in ('echo^|ipconfig^|find "2001"')do set ipv6=%%i
echo.&echo IPv6:  %ipv6%
echo.&echo �س������v6·�� &pause > nul
route add ::/0 %ipv6%

pause
