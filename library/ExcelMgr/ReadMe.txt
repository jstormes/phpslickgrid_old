

ExcelMgr is a library for importing and exporting data from a database table to an
excel worksheet.

This library spawns a long running task that forks multiple loaders.  The idea is that
by breaking the file to be loaded up into section and having a fork for each section
the load should go allow faster.

http://stackoverflow.com/questions/45953/php-execute-a-background-process


/ExcelMgr/ViewMgr
/ExcelMgr/Background
