# Business Finance Manager - Frontend

Tech stack:
- React 18
- Vite
- Tailwind CSS 3
- lucide-react (icons)

This frontend expects a Laravel API running at: `http://127.0.0.1:8000/api`.

Endpoints used:
- GET    /accounts
- POST   /accounts
- POST   /accounts/deposit
- GET    /expenses?month=YYYY-MM
- POST   /expenses
- DELETE /expenses/{id}
- GET    /bills
- POST   /bills
- DELETE /bills/{id}
- PATCH  /bills/{id}/status

## Run

```bash
cd frontend
npm install
npm run dev
```

Then open the URL printed by Vite (usually http://localhost:5173).
