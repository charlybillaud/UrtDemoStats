@ECHO off
::
:: Author: Charly Billaud
::
:: CONFIG
@SET PHP_TO_CALL=%~n0.php
:: CONFIG

::PATH TO THE PHP BINARY
@SET PATH_PHP=C:\Projets\Binaire\php-7\php.exe
@SET LOGINFO=[INFO]
@SET LOGERROR=[ERROR]

if exist %PHP_TO_CALL% (
	ECHO %LOGINFO% Begin of %PHP_TO_CALL%
	%PATH_PHP% %PHP_TO_CALL%
	ECHO %LOGINFO% End of %PHP_TO_CALL%
	pause
) else (
	ECHO %LOGERROR% %PHP_TO_CALL% script does not exist!
)