@echo off
echo Please be patient when waiting for 
echo the data to load into MySQL.
echo .
echo .
echo The loading of the data into MySQL 
echo may take 2 minutes or more.
echo .
echo .
echo mysql -uroot -p 
mysql -uroot -p < createdb.sql
echo If the installation of ITIS was successful, 
echo then itis should be in the list above.
echo .
echo .
pause
