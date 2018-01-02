REDCap ETL
================================================

REDCap ETL (Extract, Transform, and Load)

* **Extract:** Uses the REDCap API to read records from a REDCap project, plus a 'map' that defines which variables go to which tables in the user's database.
* **Transform:** Uses the 'map' to transform REDCap records into database records.
* **Load:** Drops/Creates tables and inserts Rows into the user's database.
