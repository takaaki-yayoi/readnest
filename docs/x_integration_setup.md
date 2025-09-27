# X (Twitter) Integration Setup Guide

This guide explains how to set up and use the X integration feature in ReadNest.

## Overview

The X integration feature allows users to:
- Connect their own X (Twitter) account to ReadNest
- Post reading activities to their own X account instead of the default @dokusho account
- Control which types of events are posted to X
- Disconnect their X account at any time

## Database Migration

Before using the X integration feature, you need to apply the database migration:

1. Log in as an admin
2. Navigate to `/admin/apply_x_oauth_migration.php`
3. The migration will add the following columns to the `b_user` table:
   - `x_oauth_token` - X OAuth access token
   - `x_oauth_token_secret` - X OAuth access token secret
   - `x_screen_name` - X account screen name
   - `x_user_id` - X user ID
   - `x_connected_at` - When X account was connected
   - `x_post_enabled` - Whether to post to user X account
   - `x_post_events` - Bitmask for which events to post

## User Flow

### Connecting X Account

1. User goes to Account Settings (`/account.php`)
2. Clicks on "X連携設定" (X Integration Settings) tab
3. Clicks "Xアカウントを連携する" (Connect X Account) button
4. Gets redirected to X authorization page
5. Authorizes ReadNest to access their X account
6. Gets redirected back to ReadNest with connection confirmed

### Managing X Settings

Once connected, users can:
- Enable/disable posting to their own X account
- Select which events to post:
  - Starting to read a book (value: 1)
  - Reading progress updates (value: 2)
  - Finishing a book (value: 4)
  - Writing a review (value: 8)

### Disconnecting X Account

Users can disconnect their X account at any time from the X Integration Settings tab.

## Technical Details

### OAuth Flow

The implementation uses OAuth 1.0a:
1. `/x_connect.php` - Initiates OAuth flow, gets request token
2. User authorizes on X
3. `/x_callback.php` - Handles callback, exchanges for access token
4. `/x_disconnect.php` - Removes stored credentials

### Posting Logic

The posting logic in `library/x_api.php` has been updated:
- If user has connected their X account, posts go to their account
- If not connected or posting disabled, posts go to @dokusho account
- The same templates are used for both posting methods

### Security Considerations

- OAuth tokens are stored encrypted in the database
- Only users with public diaries can post to X
- Users must explicitly enable X posting after connecting their account
- CSRF protection should be implemented for disconnect action

## Configuration

Make sure the X API credentials are properly configured in `/config/x_api.php`:
- `X_API_KEY` - API Key (Consumer Key)
- `X_API_SECRET` - API Secret (Consumer Secret)
- `X_ACCESS_TOKEN` - Default @dokusho account access token
- `X_ACCESS_TOKEN_SECRET` - Default @dokusho account access token secret

## Troubleshooting

### Common Issues

1. **OAuth callback fails**
   - Check that the callback URL is correctly configured in X app settings
   - Ensure HTTPS is properly configured

2. **Posts not appearing**
   - Verify user has public diary setting enabled
   - Check that X posting is enabled in user settings
   - Review error logs for API failures

3. **Connection times out**
   - X API might be rate limited
   - Check network connectivity

### Error Messages

- `request_token_failed` - Failed to get OAuth request token
- `invalid_token` - Invalid request token response
- `token_mismatch` - OAuth token doesn't match session
- `access_token_failed` - Failed to exchange for access token
- `storage_failed` - Failed to store credentials in database

## Future Enhancements

Potential improvements:
- Add ability to customize tweet templates per user
- Implement tweet preview before posting
- Add option to cross-post to multiple social platforms
- Implement OAuth 2.0 when X fully supports it for user context