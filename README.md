# Mautic API for Laravel
Free and Open Source Marketing Automation API

## Requirements
* PHP ^8.2 with cURL support
* Laravel ^12.0 or ^13.0 (Laravel 13 requires PHP ^8.3)
* Mautic API Library ^4.0, Guzzle ^7.17 and Graham Campbell Manager ^5.3

Version **4.0.0** adds Laravel 13 support and upgrades the Mautic SDK to version 4.
PHP 8.2–8.5 are covered by the compatibility matrix, subject to Laravel's own PHP requirements.
PHP 8.6 checks are experimental until Laravel officially lists support for it.
See [CHANGELOG.md](CHANGELOG.md) for the compatibility investigation, upgrade notes and validation limits.

### Previous releases

### v 2.0.7
* Laravel ^8.0
* PHP ^7.3 or newer

### v 3.0.1
* Laravel ^9.0
* PHP ^8.0.2 or newer

### v 3.0.2
* Laravel ^10.0
* PHP ^8.1 or newer

### v 3.0.3
* Laravel ^11.0
* PHP ^8.2 or newer

### v 3.0.4
* Laravel ^12.0
* PHP ^8.2 or newer

## Mautic Setup
The API must be enabled in Mautic.

Within Mautic, go to the Configuration page (located in the Settings menu) and under API Settings enable Mautic's API. You can also choose which OAuth2 protocol to use here.

After saving the configuration, go to the API Credentials page (located in the Settings menu) and create a new client. Enter the `callback/redirect` URI ( must be `https://your-app.com/mautic/application/register`) that the request will be sent from. Click Apply then copy the `Client ID` and `Client Secret` to the application that will be using the API.

## Installation
First, you'll need to require the package with Composer:
```sh
composer require costamateus/laravel-mautic-api
```
Laravel automatically discovers the service provider and facade. If package discovery is disabled,
register `Triibo\Mautic\MauticServiceProvider::class` in `bootstrap/providers.php`.
Finally, from the command line run `php artisan vendor:publish --provider="Triibo\Mautic\MauticServiceProvider"` to publish the default configuration file.
This publishes `config/mautic.php` and the consumer migration. The model is autoloaded from the package.

Then, run `php artisan migrate` migration command to create consumer table in your database.

## Configuration
You need to add your `client id`, `client secret` and  `callback url` for OAuth2 or `username` and `password` for BasicAuth in `config/mautic.php`.
Or put it in your `.env` file.
```
## OAuth2
MAUTIC_BASE_URL="https://your-mautic.com"
MAUTIC_PUBLIC_KEY="publicKey"
MAUTIC_SECRET_KEY="secretKey"
MAUTIC_CALLBACK="https://your-app.com/mautic/application/register"
## or BasicAuth
MAUTIC_BASE_URL="https://your-mautic.com"
MAUTIC_USERNAME="username"
MAUTIC_PASSWORD="password"
```

## Authorization
This Library only supports `OAuth2` and `BasicAuth` Authentication.
For OAuth2 you need to create a `OAuth2` client in order to use the api.
For BasicAuth, set `"default" => "basic"` in `config/mautic.php`; no OAuth registration or token table is needed.
The existing `MAUTIC_AUTH_VERSION` setting does not select the default connection.

## Registering Application
For OAuth2, visit this URL once in a browser to authorize the application. The OAuth handshake uses a native PHP session;
the browser must retain its session cookie across the redirect. BasicAuth does not use this route.
```url
http://your-app.com/mautic/application/register
```


# Usage
Add Mautic Facade in your controller.
```php
use Triibo\Mautic\Facades\Mautic;
```
#### Send a request to mautic ( Example )
Create a new contact in mautic.
```php
$params = array(
    'firstname' => 'Prince',
    'lastname'  => 'Ali Khan',
    'email'     => 'princealikhan08@gmail.com'
);

Mautic::request('POST','contacts/new',$params);
```
Get List of all contacts
```php
Mautic::request('GET','contacts');
```
Get a unique contact
```php
Mautic::request('GET','contacts/1');
//where 1 is unique id for a contact.
```

Delete a contact
```php
Mautic::request('Delete','contacts/1/delete');
```
##### And many more endpoints support by mautic.
### List of Endpoints supported by Mautic.

#### Assets
```json
[
    "assets",
    "assets/new",
    "assets/{asset_id}",
    "assets/{asset_id}/edit",
    "assets/{asset_id}/delete"
]
```

#### Campaigns
```json
[
    "campaigns",
    "campaigns/new",
    "campaigns/{campaign_id}",
    "campaigns/{campaign_id}/contacts",
    "campaigns/clone/{campaign_id}",
    "campaigns/{campaign_id}/edit",
    "campaigns/{campaign_id}/delete",
    "campaigns/{campaign_id}/contact/{contact_id}/add",
    "campaigns/{campaign_id}/contact/{contact_id}/remove"
]
```

#### Categories
```json
[
    "categories",
    "categories/new",
    "categories/{category_id}",
    "categories/{category_id}/edit",
    "categories/{category_id}/delete"
]
```

#### Companies
```json
[
    "companies",
    "companies/new",
    "companies/{company_id}",
    "companies/{company_id}/edit",
    "companies/{company_id}/delete",
    "companies/{company_id}/contact/{contact_id}/add",
    "companies/{company_id}/contact/{contact_id}/remove"
]
```

#### Contacts
```json
[
    "contacts",
    "contacts/batch/new",
    "contacts/batch/edit",
    "contacts/batch/delete",
    "contacts/new",
    "contacts/{contact_id}",
    "contacts/{contact_id}/edit",
    "contacts/{contact_id}/delete",
    "contacts/{contact_id}/dnc/{channel}/add",
    "contacts/{contact_id}/dnc/{channel}/remove",
    "contacts/{contact_id}/utm/add",
    "contacts/{contact_id}/utm/{utm_id}/remove",
    "contacts/{contact_id}/points/plus/{points}",
    "contacts/{contact_id}/points/minus/{points}",
    "contacts/list/owners",
    "contacts/list/fields",
    "contacts/{contact_id}/notes",
    "contacts/{contact_id}/segments",
    "contacts/{contact_id}/campaigns"
    "contacts/{contact_id}/events",
    "contacts/{contact_id}/activity",
    "contacts/activity",
    "contacts/{contact_id}/companies",
    "contacts/{contact_id}/devices"
]
```

#### Data
```json
[
    "data",
    "data/{type}",
    "data/emails.in.time",
    "data/sent.email.to.contacts",
    "data/most.hit.email.redirects"
]
```

#### Dynamic Content
```json
[
    "dynamiccontents",
    "dynamiccontents/new",
    "dynamiccontents/{dynamiccontent_id}",
    "dynamiccontents/{dynamiccontent_id}/edit",
    "dynamiccontents/{dynamiccontent_id}/delete"
]
```

#### Emails
```json
[
    "emails",
    "emails/new",
    "emails/{email_id}",
    "emails/{email_id}/edit",
    "emails/{email_id}/delete",
    "emails/{email_id}/send",
    "emails/reply/{tracking_hash}"
]
```

#### Fields
```json
[
    "fields/company",
    "fields/company/new",
    "fields/company/{company_id}",
    "fields/company/{company_id}/edit",
    "fields/company/{company_id}/delete",
    "fields/contact",
    "fields/contact/new",
    "fields/contact/{contact_id}",
    "fields/contact/{contact_id}/edit",
    "fields/contact/{contact_id}/delete"
]
```

#### Files
```json
[
    "files/images",
    "files/{dir}/new",
    "files/{dir}/{file}/delete"
]
```

#### Forms
```json
[
    "forms",
    "forms/new",
    "forms/{form_id}",
    "forms/{form_id}/edit",
    "forms/{form_id}/delete",
    "forms/{form_id}/fields/delete",
    "forms/{form_id}/actions/delete",
    "forms/{form_id}/submissions",
    "forms/{form_id}/submissions/contact/{contact_id}",
    "forms/{form_id}/submissions/{submission_id}"
]
```

#### Marketing Messages
```json
[
    "messages",
    "messages/new",
    "messages/{message_id}",
    "messages/{message_id}/edit",
    "messages/{message_id}/delete"
]
```

#### Notes
```json
[
    "notes",
    "notes/new",
    "notes/{note_id}",
    "notes/{note_id}/edit",
    "notes/{note_id}/delete"
]
```

#### Notifications
```json
[
    "notifications",
    "notifications/new",
    "notifications/{notification_id}",
    "notifications/{notification_id}/edit",
    "notifications/{notification_id}/delete"
]
```

#### Pages
```json
[
    "pages",
    "pages/new",
    "pages/{page_id}",
    "pages/{page_id}/edit",
    "pages/{page_id}/delete"
]
```

#### Points Actions
```json
[
    "points",
    "points/new",
    "points/{point_id}",
    "points/{point_id}/edit",
    "points/{point_id}/delete",
    "points/actions/types",
    "points/triggers",
    "points/triggers/new",
    "points/triggers/{point_id}",
    "points/triggers/{point_id}/edit",
    "points/triggers/{point_id}/delete",
    "points/triggers/{point_id}/events/delete",
    "points/triggers/events/types"
]
```

#### Reports
```json
[
    "reports",
    "reports/{report_id}"
]
```

#### Roles
```json
[
    "roles",
    "roles/new",
    "roles/{role_id}",
    "roles/{role_id}/edit",
    "roles/{role_id}/delete"
]
```

#### Segments
```json
[
    "segments",
    "segments/new",
    "segments/{segment_id}",
    "segments/{segment_id}/edit",
    "segments/{segment_id}/delete",
    "segments/{segment_id}/contact/{contact_id}/add",
    "segments/{segment_id}/contact/{contact_id}/remove"
]
```

#### Text messages
```json
[
    "smses",
    "smses/new",
    "smses/{sms_id}",
    "smses/{sms_id}/edit",
    "smses/{sms_id}/delete",
    "smses/{sms_id}/contact/{contact_id}/send"
]
```

#### Stages
```json
[
    "stages",
    "stages/new",
    "stages/{stage_id}",
    "stages/{stage_id}/edit",
    "stages/{stage_id}/delete",
    "stages/{stage_id}/contact/{contact_id}/add",
    "stages/{stage_id}/contact/{contact_id}/remove"
]
```

#### Stats
```json
[
    "stats",
    "stats/{table}"
]
```

#### Tags
```json
[
    "tags",
    "tags/new",
    "tags/{tag_id}",
    "tags/{tag_id}/edit",
    "tags/{tag_id}/delete"
]
```

#### Themes
```json
[
    "themes",
    "themes/new",
    "themes/{theme_name}",
    "themes/{theme_name}/delete"
]
```

#### Tweets
```json
[
    "tweets",
    "tweets/new",
    "tweets/{tweet_id}",
    "tweets/{tweet_id}/edit",
    "tweets/{tweet_id}/delete"
]
```

#### Users
```json
[
    "users",
    "users/new",
    "users/{user_id}",
    "users/{user_id}/edit",
    "users/{user_id}/delete",
    "users/self",
    "users/{user_id}/permissioncheck"
]
```

#### Webhooks
```json
[
    "hooks",
    "hooks/new",
    "hooks/{hook_id}",
    "hooks/{hook_id}/edit",
    "hooks/{hook_id}/delete",
    "hooks/triggers"
]
```

Please refer to [Documentation](https://developer.mautic.org).
for all customizable parameters.

## Development

```sh
composer update --no-plugins
composer test
composer audit
```

The compatibility check uses an in-memory SQLite database and a local HTTP server.
It requires `pdo_sqlite`, `proc_open` and an available loopback port, and never contacts a real Mautic server.
