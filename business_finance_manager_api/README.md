# Business Finance Manager - Laravel Backend (skeleton)

This folder does NOT contain a full Laravel project.
You should:

1. Create a fresh Laravel app:

```bash
composer create-project laravel/laravel business_finance_manager_api
cd business_finance_manager_api
```

2. Configure PostgreSQL in `.env`:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=business_finance_manager
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

3. Create the following files in your Laravel project and copy the contents
from the matching files in this `backend-laravel` folder.

- `app/Models/Account.php`
- `app/Models/Expense.php`
- `app/Models/Bill.php`
- `app/Http/Controllers/AccountController.php`
- `app/Http/Controllers/ExpenseController.php`
- `app/Http/Controllers/BillController.php`
- `database/migrations/...create_accounts_table.php`
- `database/migrations/...create_expenses_table.php`
- `database/migrations/...create_bills_table.php`
- `database/seeders/AccountsSeeder.php`
- `database/seeders/DatabaseSeeder.php` (update run() to call AccountsSeeder)
- `routes/api.php` (update API routes)
- `config/cors.php` (allow http://localhost:5173)

4. Run migrations and seeders:

```bash
php artisan migrate
php artisan db:seed
```

5. Run the API server:

```bash
php artisan serve
```

API base URL will be: `http://127.0.0.1:8000/api`.
