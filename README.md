# Fake SteamPulse

Fake SteamPulse is a hobby Telegram bot project inspired by the SteamPulse Web project. It provides live TF2 key and ticket prices for multiple regions, fetched directly from the Steam Community Market.

## Features

- Show **TF2 Key & Ticket** prices for USA, Argentina, Turkey, Ukraine, Russia, India, Brazil, Kazakhstan.
- Inline **glass-style buttons** for easy navigation.
- Sends price results in a **clean, readable format**.

## Bot Commands

- `/start` – Show main menu with Key, Ticket, and About buttons.
- `/about` – Show about information and project links.

## Deployment

This bot is ready to deploy on [Render.com](https://render.com).

### Environment Variables

- `TELEGRAM_BOT_TOKEN` – Your Telegram bot token.

### Docker

The project comes with a Dockerfile for CLI-based deployment.

```bash
docker build -t fakesteampulsefun .
docker run -d -e TELEGRAM_BOT_TOKEN=<YOUR_BOT_TOKEN> fakesteampulsefun
```

### Webhook

After deploying, set your webhook:
```
https://api.telegram.org/bot<YOUR_BOT_TOKEN>/setWebhook?url=https://fakesteampulsefun.onrender.com

```

Replace <YOUR_BOT_TOKEN> with your Telegram bot token.

### Credits

Original project: [SteamPulse_Web](https://github.com/CodeMageIR/SteamPulse_Web)

## License

This project is licensed under the **GNU General Public License v3.0 (GPL-3.0)**.  
