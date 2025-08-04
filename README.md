# Molecule ALCO Event QR
Installation
```
composer install
php artisan key:generate
php artisan telescope:install
php artisan migrate
php artisan filament:install --panels
php artisan vendor:publish --tag="filament-shield-config"
php artisan shield:setup
php artisan shield:install admin
php artisan shield:generate --all
php artisan db:seed
```