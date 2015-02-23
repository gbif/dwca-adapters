ReadmeMySQL

Instructions for ITIS database MySQL download

1. The MySQL software should be installed on your system.
   To obtain the MySQL software for installation go to:
     http://www.mysql.com

2. Download the ITIS MySQL zip file from: 
     http://www.itis.gov/downloads 
   using your browser, such as MS Internet Explorer.

   The ITIS zip file will be of the format: 
     itisMySQL111908.zip

   Note: That throughout these instructions the 
   file "itisMySQL111908" has "111908" as its 
   date.  Your zip file will have its own 
   date and not "111908" within the file name.
   
3. Installation instructions.

  Windows Instructions
  
    After downloading the ITIS MySQL zip file, that 
    file "itisMySQL111908.zip" will now reside on your 
    system as a Compressed (zipped) Folder.
  
    Use WinZip or some other software to extract the 
    following 4 files from your downloaded ITIS zip file:
      installdb.bat createdb.sql ITIS.sql ReadmeMySQL.txt

    Use "My Computer" to go to where you have 
    extracted those 4 files.
  
    On your Windows system, make sure that 
    you can run the MySQL software as the
    MySQL user root on your PC.

    If you can't run MySQL as the MySQL user root
    on your PC then edit file "installdb.bat"
    and replace root with your chosen MySQL user name.
  
    Make sure that the MySQL Server is running on
    your PC prior to double clicking on the icon.

    Caution:  When you install this new ITIS database 
    in MySQL on your PC, the SQL command to drop any 
    preexisting database called "itis" is first executed 
    before creating and loading the new "itis" database.

    To install the ITIS database onto MySQL on your PC, 
    double click on the icon:
       installdb.bat
  
    Note: You will be prompted for your MySQL password after 
    clicking on the icon.
    Be patient when waiting for the data to load into MySQL. 
    The loading of the data may take 2 minutes or more.
  
  Linux Instructions
  
    On Linux, execute the following command:
      mysql -uroot -p < createdb.sql
  
    Note: You may use a different MySQL user name other than root.

  Mac OS X Instructions
  
    On Mac OS X, execute the following command:
      mysql -uroot -p < createdb.sql
  
    Note: Again, you may use a different MySQL user name other than root.

