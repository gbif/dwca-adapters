# DwC-Adapters
Created: 2010
Created By: Michael Giddens (mikegiddens@silverbiology.com)

PHP scripts to generate dwc checklist archives for various sources

# Requirements:
- PHP 5.2.x+
- MySQL (Optional for 2 Sources)

# Sources:

0. all - All sources 1-6 below
1. usda - USDA Plants
2. tol - Tree Of Life
3. ncbi - NCBI
4. grin - GRIN   // Needs a Mysql Database (see MySql setup below)
5. itis - ITIS
6. col - Catalogue of Life

# Configuration:
1) Copy the default.config.php to config.php and edit the information.

# Using DwC-Adapters 

### via command line:

1. Go to shell program.
2. Go to the main folder for this project.
3. Type "php index.php {source}".

### Setting up cronjob to execute source generation:
```
========================================================================
.---------------- minute (0 - 59) 
|  .------------- hour (0 - 23)
|  |  .---------- day of month (1 - 31)
|  |  |  .------- month (1 - 12) OR jan,feb,mar,apr ... 
|  |  |  |  .---- day of week (0 - 6) (Sunday=0 or 7)  OR sun,mon,tue,wed,thu,fri,sat 
|  |  |  |  |
*  *  *  *  *  php /{path_to_script}/index.php {source}
========================================================================
```

### MySql Setup:
To be able to use itis and col you must be runnign a mysql database.

1. Create 2 databases like itis & col.
2. Create a user that has full permissions for these 2 databases.
3. Edit the config.php file and add in the db connection information.
4. Run the 2 sql files to build the table structure.
