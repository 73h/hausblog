# a blog with telegram integration

set Telegram Webhook

    https://api.telegram.org/bot{bot_token}/setWebhook?url={url_to_send_updates_to}

use ngrok for local development

    ngrok http -region eu 8081
