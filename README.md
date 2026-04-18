# ⚔️ MTG Life Counter

A real-time multiplayer life counter for **Magic: The Gathering**, built with Laravel, Livewire, and WebSockets. Every player connects from their own phone — life changes, commander damage, and counters sync instantly across all devices at the table.

![PHP 8.4](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white)
![Laravel 13](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)
![Livewire 4](https://img.shields.io/badge/Livewire-4-FB70A9?logo=livewire&logoColor=white)
![Tailwind CSS 4](https://img.shields.io/badge/Tailwind-4-06B6D4?logo=tailwindcss&logoColor=white)

---

## ✨ Features

### Core
- **Real-time sync** — Life totals update instantly across all connected devices via [Laravel Reverb](https://reverb.laravel.com) WebSockets
- **No account required** — Share a game code, everyone joins from their browser
- **Spectator mode** — Watch without joining as a player
- **Re-join support** — Reconnect as an existing player if your browser closes

### Life Tracking
- **Tap to adjust** — Single tap on `+` / `-` changes life by 1
- **Long-press for custom amounts** — Hold `+` or `-` for 400ms to open a numeric input modal (add/subtract any amount)
- **Set exact HP** — Via the player menu (`...` → "Leben setzen")
- **Haptic feedback** — Vibration on tap (40ms) and incoming damage (SOS pattern)

### Commander Support
- **Commander damage tracking** — Per-opponent damage counters with partner commander support
- **Player counters** — Poison, Energy, Experience, Storm, Commander Tax
- **Player elimination** — Mark players as defeated (greyscale + "DEFEATED" overlay)
- **Seat swapping** — Rearrange player positions without restarting

### Layout Engine
- **Grid layout** — Equal-sized cards for all players
- **Focused layout** — Your card is larger, others are smaller
- **6-rect layout** — Rectangular table arrangement
- **Board rotation** — 0° / 90° / 180° / 270° rotation with CSS transforms
- **Persistent preferences** — Layout and rotation are saved per device via session

### Resilience
- **Auto-reconnect** — Polls every 10s + syncs on screen wake (`visibilitychange`)
- **Optimistic UI** — Life changes feel instant, server confirms in background

---

## 🚀 Quick Start (Development)

### Prerequisites
- PHP 8.4+
- Composer
- Node.js 20+
- SQLite

### Setup

```bash
# Clone and install
git clone <repo-url> && cd mtg-life-counter-app
composer install
npm install

# Environment
cp .env.example .env
php artisan key:generate

# Database
touch database/database.sqlite
php artisan migrate

# Start everything (web server, queue, reverb, vite)
composer run dev
```

### LAN Play (phones on same WiFi)

1. Find your local IP (e.g. `192.168.178.125`)
2. Update `.env`:
   ```env
   APP_URL=http://192.168.178.125:8000
   ASSET_URL=http://192.168.178.125:8000
   REVERB_HOST="192.168.178.125"
   VITE_REVERB_HOST="192.168.178.125"
   ```
3. Start Reverb separately: `php artisan reverb:start`
4. Start dev server: `composer run dev`
5. Open `http://192.168.178.125:8000/game/CREATE` on any phone

---

## 🐳 Docker Deployment (Production)

### 1. Configure

Edit `docker-compose.yml` and set your server's public IP or domain:

```yaml
args:
  VITE_REVERB_HOST: "mtg.example.com"   # ← Your server IP/domain
```

### 2. Build & Run

```bash
docker compose up -d --build
```

### 3. Access

| Service    | URL                            |
|------------|--------------------------------|
| Web        | `http://your-server`           |
| WebSocket  | `ws://your-server:8080`        |

### Architecture

The container runs 4 processes via Supervisor:

| Process     | Role                                    |
|-------------|------------------------------------------|
| **Nginx**   | Web server (port 80), static assets     |
| **PHP-FPM** | 20 worker processes for HTTP requests   |
| **Reverb**  | WebSocket server (port 8080)            |
| **Queue**   | Background job processing               |

### Persistence

SQLite database and storage are persisted via Docker named volumes:
- `app-data` → `/app/database`
- `app-storage` → `/app/storage`

### Rebuilding

The `VITE_REVERB_HOST` is baked into the JavaScript bundle at build time. If you change your server's IP or domain, you must rebuild:

```bash
docker compose up -d --build
```

---

## 🎮 How to Play

1. One player creates a game → gets a **game code** (e.g. `XNNOL`)
2. Share the URL with everyone at the table
3. Each player enters their name and picks a color
4. Play Magic! Tap `+`/`-` to track life, use the menus for commander damage and counters

---

## 🛠 Tech Stack

| Layer      | Technology                                         |
|------------|-----------------------------------------------------|
| Backend    | Laravel 13, PHP 8.4                                |
| Frontend   | Livewire 4, Flux UI 2, Alpine.js                  |
| Styling    | Tailwind CSS 4                                     |
| WebSockets | Laravel Reverb                                     |
| Database   | SQLite                                             |
| Production | Docker, Nginx, PHP-FPM, Supervisor                |

---

## 📝 License

MIT
