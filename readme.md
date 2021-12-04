# OPER API

![framework](https://img.shields.io/badge/LARAVEL-5.7-red.svg)
![php](https://img.shields.io/badge/PHP-7.2.10-green.svg)
![database](https://img.shields.io/badge/MYSQL-5.7.23-blue.svg)

# HOW TO INSTALL

- Update environtment in .env
- Install project dependency :
        ```sh
        $ composer install
        ```
        
- Create your database then run theese command to generate table and seed data into your database 
        ```sh
        $ php artisan migrate:refresh --seed
        ```
- install passport
        ```sh
        php artisan passport:install
        ```
- Link storage
        ```sh
        php artisan storage:link
        ```

# CODING STANDARD
- PSR2
- Tools: php_codesniffer        