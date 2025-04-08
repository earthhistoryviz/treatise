This is the repository for all treatise websites.
The main one you should change is development, which should also change other treatise repositories for *.treatise.geolex.org sites like echinoderm.treatise.geolex.org

Prettier Command: docker run -it --rm -v $(pwd):/code ghcr.io/php-cs-fixer/php-cs-fixer:3.57-php8.0 fix site

The steps to actually upload are as follows
    - Upload excel file into the db folder
    - exec into the docer container via `docker compose exec <service_name> bash` inside of aaron/live
        - you can find service name via the docker-compose file inside of live
    - Once execed, cd code/db then run php create_db.php <excel file name>
    - Now go to the site you want and login, then upload the excel file again. If issues occur, see below

Additional notes for uploading excel files: 
- The first row of the excel file should be column header names. Example, if the excel file's row 1 is credits, remove it
- The excel file should also have columns with headers named "First Occurrence" and "Last Occurrence" and "Genus". Capitalization matters. Are you sure you did this?
- displayinfo.php assumes that certain columns exist in the database and any changes in database will likely need changes in displayInfo.php
- create_db.php inside of db will take the excel file in the db folder and make column headers for the sql database
    - Looking at the code, create_db will first parse the uploaded excel file and make columns in the sql database, then it will add additional columns like `beginning_date` and `ending_stage`. These columns that are manually added are columns whose information is calculated by us and not part of the original excel file.
