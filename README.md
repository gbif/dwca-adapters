# DwC-Adapters
This PHP project provides converters for several checklist datasets from their native, proprietary and public format to a standard Darwin Core Archive
which can be indexed by GBIF ChecklistBank. The following sources are supported:

 - usda: [USDA Plants](https://www.gbif.org/dataset/705922f7-5ba5-49ab-a75d-722e3090e690)
 - tol: [Tree Of Life](https://www.gbif.org/dataset/41efd0ac-0c70-48af-9e38-b19c66d6f3e2)
 - ncbi: [NCBI](https://www.gbif.org/dataset/fab88965-e69d-4491-a04d-e3198b626e52)
 - grin: [GRIN](https://www.gbif.org/dataset/66dd0960-2d7d-46ee-a491-87b9adcfe7b1)
 - itis: [ITIS](https://www.gbif.org/dataset/9ca92552-f23a-41a8-a140-01abaa31c931)

*The source code was created by Michael Giddens (mikegiddens@silverbiology.com), contracted by GBIF in 2010.*


# Installation

### Requirements:
- PHP 5.2.x+
- MySQL (Optional for ITIS source)

### Configuration:
1) Copy the ```default.config.php``` to ```config.php``` and edit the information.

### MySql Setup:
To be able to use ITIS you must be running a mysql database (ITIS is distributed as a mysql dump).
In order to use the itis adapter you need to manually setup a new itis database once:

1. Create a database itis.
2. Create a user that has full permissions for that database.
3. Edit the config.php file and add in the db connection information.
4. Download and extract the latest ITIS mysql bulk dump from
5. Run the CreateDB.sql file to build the table structure in your new database:
   ```mysql -uroot -p itis < CreateDB.sql```


# Usage

1. cd into root folder of this project
2. execute ```php index.php {source}```
3. generated dwc archives will be in the respective sources subfolder


### GBIF installation
The dwca adapters are installed on rs.gbif.org where each source is executed by cron once a week.
The generated dwc archives are then copied to the apache server hosting http://rs.gbif.org/datasets/ which contains the files registered in GBIF.

### Docker use (2019)

This is a quick hack to get something working.  It needs to be moved to a proper build server.

```
docker build -t dwca-adapters .
docker run -it --rm dwca-adapters
$ php index.php ncbi
```
