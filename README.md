# Verifier

## Overview

Verifier is a web application based on Laravel that provides a backend endpoint `POST /api/verifications`. The endpoint accept a JSON file and verify the file based on certain rules.

## How to run locally

### 1. Clone the repo

Go to your desired folder locally, and run `git clone [repo_url]`.

### 2. Install dependencies

Ensure that `php` and `composer` are available, then run `composer install` to install the dependencies.

### 3. Prepare local .env file

Copy `.env.example` into `.env` and configure desired settings.

### 4. Generate application

Run `php artisan key:generate` to generate application key.

### 5. Run tests

Run `php artisan test` to verify the basic setup.

### 6. Initialise database

Create `database/database.sqlite` file and run `php artisan migrate` to initialise database.

### 7. Seed sample user

Run `php artisan db:seed` to seed a sample user.

### 8. Run web server

Run `php artisan serve` to start the web server. The URL `http://localhost/api/verifications` is ready to use, e.g. `curl http://localhost:8000/api/verifications -X POST -H "Accept: application/json" -H "Authorization: Bearer [token]" -F "file=@[path_to_sample_json_file]"`
