:: 以管理员运行
%1 mshta vbscript:CreateObject("Shell.Application").ShellExecute("cmd.exe","/c %~s0 ::","","runas",1)(window.close)&&exit

@shift /0
@echo off
title    添加 IPv6 路由
::color f0
mode con cols=38 lines=20
echo.&echo.&echo ======== 添加 IPv6 路由 ========&echo.
set /p host=VPNname:
setlocal enabledelayedexpansion
for /f "tokens=1,2 delims=" %%i in ('netsh interface ipv6 show address %host%') do (
        set /a n+=1
        if !n!==1 set var=%%i
)
:: 当前IPv6所在行 %var% 
for /f "tokens=1,2 delims= " %%i in ('echo "%var%"') do set ipv6=%%j

:: 或者是这样也可以，上面可能比较准确
:: for /f "tokens=16 delims= " %%i in ('echo^|ipconfig^|find "2001"')do set ipv6=%%i
echo.&echo IPv6:  %ipv6%
echo.&echo 回车后将添加v6路由 &pause > nul
route add ::/0 %ipv6%

pause
