## Firewall rules generator. Alpha Version ##
This application processes a pseudo log file and produce pseudo firewall rules, based on a pre-defined algorithm.
The log file structure should be as follows:

Source_IP_address	Destination_IP_address	Port	Protocol

Protocol: A number. ex. TCP would be 6 and UDP would be 17

###Pre-requisites###

The easiest option to run the application is to install Laravel Homestead. Detailed instructions on Homestead can be found on:
https://laravel.com/docs/5.2/homestead#installation-and-setup. Perform the following:

1. Install vagarent
2. Install virtualbox
3. Follow the instructions  on the above link to install Laravel Homestead



###Installation process:###

1. `wget https://github.com/nadhemj/logs2rules/archive/master.zip`
2. `unzip master.zip`
3. `cd logs-to-rules`
4. `composer install`
5. Create a database and inform *.env* (remove *.example*, change DB configuration in *.env*)(alternatively you can change the database entry to homstead in .env file, DB_DATABASE=homestead)
5. `php artisan migrate` to create tables

Now you are ready

**Access the Application**

From your browser point to the application landing page where you can upload a log file:

http://homestead.app/

**Application workflow:**


1. Log file uploaded via simple upload form.
2. Log file being parsed:

	2.1. Open log file to read with php

	2.2. Create data.sql file to gather data for database. Insert request to create temporary table for initial calculation.

	2.3. Iteratively read each line of file:

		2.3.1. Check if line contents are correct: line should not contain any other symbols than numbers and dots.

		2.3.2. Convert IPs to long numerical with ip2long php function.

		2.3.3. Apply subnet masks to converted IPs to get additional data for database (/24, /16, /8 subnets)

		2.3.4. Store results in a php string, counting each parsed line.

		2.3.5. If the file completely parsed, or counter reaches 2000 lines, string saved in data.sql file to avoid slowing. String then cleared.

	2.4. Loop to calculate tolerance (or permissiveness) for each step of algorithm and create requests for database filling:

		2.4.1. Call to a function, containing current tolerance formula (where weights can be changed), with specific parameters for each step.

		2.4.2. After getting permissiveness, create request to generate new rules set with higher permissiveness, than the previous one has.

		2.4.3. Insert request to data.sql file

		2.4.4. Insert request to update lines, which were used to generate rules set (give them parent ID) to data.sql file.

	2.5. Upload data.sql directly to database as a dump file (using exec to call MySQL command line). This allows to minimise number of connections to the database

3. After database is filled, application redirects to the results page (can be altered to notify user, tha process is finished, and then show the results)

4. We have an interactive chart, which shows number of rules and permissiveness level for each step of algorithm.

5. After clicking a certain point of chart, user gets online interactive tree table, which contains rules set for a chosen point.

5.1. By opening any node of a tree, user excludes opened node from the rules set and includes children of that node to the set.

5.2. To get the resulting rules set (.csv file), user can choose a raw file, which contains only required data for the firewall, or a user-optimised table, which contains table headers and additional data for easier file reading


**Main functions**
php:
/app/Http/Controllers/LogController.php
* index() - parse log file, generate .sql files, upload data.sql to database.
* part() - upload data2.sql to database. (improves performance and UX)
* calculateTolerance() - contains permissiveness calculation formula.
* getChartPoints() - retrieve data for chart drawing.
* getParentIds() - get the IDs to build the tree for certain chart point.
* getTree() - get tree data using incomming ID (list for initial tree building, one ID to get the opened node children).
* createTree() - prepare data for the tree structure.
* getTableContents() - get the data for the table (1 or many IDs). Called after tree build, or when a node is opened
* getOutputFile() - get raw .csv file
* getHumanOutputFile() - get radable .csv file with table headers and additional data.

Javascript:
* /public/js/app.js - file that handles '/' and '/error' pages.
* /public/js/results.js - file that handles '/results' page. Functions called from events (after window loaded or on click). Documented by comments in code

Database:
* /database/migrations/2016_07_18_062355_create_logs_table.php - contains information for main table structure and indexes.
(if edited, run `php artisan migrate:rollback`, then `php artisan migrate` to implement changes)

Views:
* /resources/views/layout.blade.php - file that contains basic template
* /resources/views/form.blade.php - template for main page 
* /resources/views/error.blade.php - template for errors page 
* /resources/views/chart.blade.php - template for results page (almost all contents dynamically generated by results.js)
