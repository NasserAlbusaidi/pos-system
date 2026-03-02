# Bite-POS Deployment Guide

## Recommended Stack

| Component | Recommended | Alternative |
|-----------|-------------|-------------|
| Server | Hetzner Cloud VPS (CX22, ~€4/mo) | DigitalOcean, Railway |
| Manager | Laravel Forge ($12/mo) or Ploi ($8/mo) | Manual setup |
| Database | MySQL 8.0 | MariaDB 10.6+ |
| PHP | 8.2+ | 8.3 |
| Web Server | Nginx | Apache |
| SSL | Let's Encrypt (auto via Forge/Ploi) | Cloudflare |
| Mail | Resend or Postmark | Mailgun, SMTP |
| Storage | Local or S3 (AWS me-south-1 Bahrain) | DigitalOcean Spaces |
| DNS | Cloudflare (free tier) | Route53 |

## Pre-Deployment Checklist

### 1. Domain & DNS
- [ ] Register domain (e.g. `bitepos.app`, `getbite.om`)
- [ ] Point DNS A record to server IP
- [ ] Configure Cloudflare (optional but recommended for GCC latency)

### 2. Server Setup
```bash
# If using Forge/Ploi, this is automated. For manual setup:
sudo apt update && sudo apt upgrade -y
sudo apt install nginx mysql-server php8.2-fpm php8.2-mysql php8.2-mbstring \
    php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath php8.2-intl \
    composer nodejs npm -y
```

### 3. Database
```bash
mysql -u root -p
CREATE DATABASE bite_pos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'bite'@'localhost' IDENTIFIED BY 'SECURE_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON bite_pos.* TO 'bite'@'localhost';
FLUSH PRIVILEGES;
```

### 4. Application Deployment
```bash
cd /home/forge/bitepos.app   # or your deployment path
git clone <repo-url> .
cp .env.example .env
php artisan key:generate
```

### 5. Production `.env` Configuration
```env
APP_NAME="Bite POS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://bitepos.app

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bite_pos
DB_USERNAME=bite
DB_PASSWORD=SECURE_PASSWORD_HERE

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=resend          # or postmark, mailgun
MAIL_FROM_ADDRESS=noreply@bitepos.app
MAIL_FROM_NAME="Bite POS"
# RESEND_KEY=re_xxxx        # if using Resend

STRIPE_KEY=pk_live_xxxx
STRIPE_SECRET=sk_live_xxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxx
CASHIER_CURRENCY=omr
CASHIER_CURRENCY_LOCALE=en_OM
STRIPE_FREE_PRICE_ID=price_xxxx
STRIPE_PRO_PRICE_ID=price_xxxx
STRIPE_SUBSCRIPTION_WEBHOOK_SECRET=whsec_xxxx

FORCE_HTTPS=true
```

### 6. Install & Build
```bash
composer install --no-dev --optimize-autoloader
npm install && npm run build

php artisan migrate --force
php artisan db:seed --class=DatabaseSeeder  # Only on first deploy
php artisan storage:link
php artisan optimize
```

### 7. Queue Worker (Systemd)
Create `/etc/systemd/system/bite-worker.service`:
```ini
[Unit]
Description=Bite POS Queue Worker
After=network.target

[Service]
User=forge
Group=forge
Restart=always
RestartSec=3
WorkingDirectory=/home/forge/bitepos.app
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable bite-worker
sudo systemctl start bite-worker
```

### 8. Scheduler (Cron)
```bash
# Add to crontab (crontab -e)
* * * * * cd /home/forge/bitepos.app && php artisan schedule:run >> /dev/null 2>&1
```

### 9. Nginx Configuration
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name bitepos.app;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name bitepos.app;
    root /home/forge/bitepos.app/public;

    ssl_certificate /etc/letsencrypt/live/bitepos.app/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/bitepos.app/privkey.pem;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Cache static assets
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### 10. SSL Certificate
```bash
# If not using Forge/Ploi auto-SSL:
sudo certbot --nginx -d bitepos.app
```

## Post-Deployment Verification

### Functional Checks
- [ ] Landing page loads at `https://bitepos.app`
- [ ] Registration flow works (creates shop + redirects to onboarding)
- [ ] Login works for admin user
- [ ] Shop dashboard loads with charts
- [ ] POS terminal is functional (add to cart, pay)
- [ ] Guest menu loads at `/menu/{slug}`
- [ ] QR code generation works
- [ ] Kitchen display shows orders
- [ ] Order lifecycle: unpaid -> paid -> preparing -> ready -> completed
- [ ] Shift report renders data correctly
- [ ] Reports dashboard with charts loads
- [ ] Settings page saves correctly
- [ ] Staff CRUD works
- [ ] PrintNode integration (if configured)
- [ ] Stripe webhook receives test events

### Security Checks
- [ ] `APP_DEBUG=false` confirmed
- [ ] HTTPS forced (HTTP redirects to HTTPS)
- [ ] Security headers present (check with `curl -I https://bitepos.app`)
- [ ] Session cookie has `Secure` and `HttpOnly` flags
- [ ] No `.env` accessible via browser (`https://bitepos.app/.env` returns 403/404)
- [ ] Super admin login works

### Performance Checks
- [ ] `php artisan optimize` run
- [ ] Static assets cached (check response headers)
- [ ] Service worker registered and caching
- [ ] PWA installable from Chrome/Safari

## Stripe Webhook Setup

1. Go to Stripe Dashboard > Developers > Webhooks
2. Add endpoint: `https://bitepos.app/webhooks/stripe`
   - Events: `checkout.session.completed`, `payment_intent.succeeded`
3. Add endpoint: `https://bitepos.app/webhooks/stripe/subscription`
   - Events: `customer.subscription.created`, `customer.subscription.updated`, `customer.subscription.deleted`, `invoice.payment_succeeded`, `invoice.payment_failed`
4. Copy webhook signing secrets to `.env`

## Backups

### Automated Database Backup
```bash
# Add to crontab — daily at 3am
0 3 * * * mysqldump -u bite -p'PASSWORD' bite_pos | gzip > /home/forge/backups/bite-$(date +\%Y\%m\%d).sql.gz
# Keep last 30 days
0 4 * * * find /home/forge/backups/ -name "bite-*.sql.gz" -mtime +30 -delete
```

### Manual Backup
```bash
php artisan down
mysqldump -u bite -p bite_pos > backup-$(date +%Y%m%d-%H%M).sql
php artisan up
```

## Monitoring

- **Uptime:** UptimeRobot or Better Stack (free tier) — monitor `https://bitepos.app/up`
- **Errors:** Laravel logs in `storage/logs/laravel.log`
- **Queue:** Check `failed_jobs` table periodically

## Updating

```bash
cd /home/forge/bitepos.app
php artisan down
git pull origin main
composer install --no-dev --optimize-autoloader
npm install && npm run build
php artisan migrate --force
php artisan optimize
php artisan up
sudo systemctl restart bite-worker
```
