# Plugin: API Calls by Leuchtfeuer

## Overview / Purpose / Features
This plugin allows Mautic administrators to send generic outbound API calls as a campaign action. You can configure API requests to be triggered automatically as part of your campaign workflows.

### Key Features:
- Add a custom campaign action to send API requests
- Configure API endpoint, method, headers, and payload
- Use tokens to personalize requests
- Works with Mautic 5.x (minimum 5.2)

## Requirements / Version Support
- Mautic 5.x (minimum 5.2)
- PHP >= 8.0

## Installation
### Composer
```bash
composer require leuchtfeuer/mautic-apicalls-bundle
```

### Manual Installation
- Download the plugin
- Unzip to the Mautic `plugins` directory
- Rename folder to `LeuchtfeuerAPICallsBundle`
- In the Mautic backend, go to the `Plugins` page as an administrator
- Click on the `Install/Upgrade Plugins` button to install the Plugin
- OR run:
  - `php bin/console cache:clear`
  - `php bin/console mautic:plugins:reload`

## Configuration
After installation, navigate to Settings → Plugins → API Calls by Leuchtfeuer and configure your API endpoints and settings as needed.

## Usage
- In the Campaign Builder, add the "API Request Action" to your campaign.
- Configure the API request details in the action settings.
- The plugin will send the API request when the campaign action is executed.

## Troubleshooting
- Make sure you have installed and enabled the plugin.
- If the action does not appear, clear the cache and reload plugins:
  - `php bin/console cache:clear`
  - `php bin/console mautic:plugins:reload`
- If you update the plugin code, repeat the above commands.

## Change log
- See [GitHub](https://github.com/Leuchtfeuer/mautic-APICalls-bundle) for updates.

## Sponsoring & Commercial Support
For support or custom features, contact **mautic-plugins@leuchtfeuer.com**

## License
This plugin is licensed under the GPL-3.0-or-later License.

## Resources / Further Readings
- [Mautic Documentation](https://docs.mautic.org)
- [Leuchtfeuer Plugins for Mautic](https://leuchtfeuer.com/mautic)

