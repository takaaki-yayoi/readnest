# X (Twitter) API Integration Setup

This document explains how to configure the X API integration for ReadNest.

## Prerequisites

1. X Developer Account
2. X API v2 access
3. An app created in the X Developer Portal

## Configuration Steps

1. **Get your API credentials from X Developer Portal:**
   - Go to https://developer.twitter.com/
   - Navigate to your app settings
   - Find the following credentials:
     - API Key (Consumer Key)
     - API Secret (Consumer Secret)
     - Access Token
     - Access Token Secret
     - Bearer Token

2. **Edit the configuration file:**
   
   Open `/config/x_api.php` and fill in your credentials:
   
   ```php
   define('X_API_KEY', 'your-api-key-here');
   define('X_API_SECRET', 'your-api-secret-here');
   define('X_ACCESS_TOKEN', 'your-access-token-here');
   define('X_ACCESS_TOKEN_SECRET', 'your-access-token-secret-here');
   define('X_BEARER_TOKEN', 'your-bearer-token-here');
   ```

3. **Enable/Disable posting:**
   
   You can enable or disable X posting by changing:
   ```php
   define('X_POST_ENABLED', true); // Set to false to disable
   ```

## How It Works

The system automatically posts to X (from the @dokusho account) when users with public diaries (`diary_policy = 1`):

1. **Start reading a book** - Posts: "「Book Title」を読み始めました！ #読書記録 #ReadNest"
2. **Finish reading a book** - Posts: "「Book Title」を読み終わりました！ #読書記録 #ReadNest"
3. **Add a review with rating** - Posts: "「Book Title」のレビューを投稿しました。評価: ★★★☆☆ #読書記録 #ReadNest"

## Privacy

- Only users with `diary_policy = 1` (public diary) will have their activities posted
- Users with `diary_policy = 0` (private diary) will NOT have any posts made

## Customization

You can customize the post templates by editing these constants in `x_api.php`:

```php
define('X_TEMPLATE_START_READING', '「%s」を読み始めました！ #読書記録 #ReadNest');
define('X_TEMPLATE_FINISH_READING', '「%s」を読み終わりました！ #読書記録 #ReadNest');
define('X_TEMPLATE_ADD_REVIEW', '「%s」のレビューを投稿しました。評価: %s #読書記録 #ReadNest');
```

## Troubleshooting

Check the error logs for messages starting with `[X API]` to debug any issues.

Common issues:
- Missing API credentials
- Invalid API credentials
- Rate limits exceeded
- User has private diary (`diary_policy = 0`)

## Security Notes

- Never commit the `x_api.php` file with real credentials to version control
- Add `/config/x_api.php` to your `.gitignore` file
- Keep your API credentials secure and rotate them regularly