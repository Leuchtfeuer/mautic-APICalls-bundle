# Plugin: API Calls by Leuchtfeuer Test

## Overview / Purpose / Features
This feature introduces a new campaign action in Mautic that allows campaigns to send outbound API requests.

### Key Features:
- New campaign action: Send API request

- Configurable method: Choose between GET, POST, PUT, or PATCH.

- Custom body: Define the raw request body in a textarea.

- **Response tracking**: Capture raw response headers and body and make them visible in the contact's timeline history.

- **Contact field storage**: Store response body data in selected contact fields with optional regex processing.

- **GET request parameters**: Support for URL parameters when using GET method (e.g., `email={contactfield=email}&category=7`).

## Requirements / Version Support
- Mautic 6.0
- PHP >= 8.1

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

### Basic Usage
- In the Campaign Builder, add the "API Request Action" to your campaign.
- Configure the API request details in the action settings.
- The plugin will send the API request when the campaign action is executed.

### Method Configuration
Choose from the following HTTP methods:
- **GET**: For retrieving data from endpoints
- **POST**: For sending data to create new resources
- **PUT**: For updating existing resources
- **PATCH**: For partial updates

### GET Request Parameters
When using the GET method, you can define URL parameters in a dedicated field:
- Format: `email={contactfield=email}&category=7`
- Use `{contactfield=fieldname}` to insert contact field values
- The leading `?` is automatically added - do not include it in your parameter string

### Contact Field Storage
Store API response data directly in contact fields:
1. **Contact Field Selection**: Choose from available text-type contact fields where the response body should be stored
2. **Regex Processing**: Apply optional regular expressions to extract specific data from the response before storing
   - Use standard regex patterns to filter or transform response data
   - Leave empty to store the entire response body

#### Regex Examples
Use these placeholders to extract values from a JSON-like string:

**Pattern 1**: Capture a single value from a key
- Example: `'value_key_to_get': '12345'` → `12345`
- Placeholder: `1: /"value_key_to_get"\s*:\s*"([^"]+)"/`

**Pattern 2**: Capture multiple values from an array
- Example: `'value_key_to_get': ['Value1','Value2']` → `'Value1','Value2'`
- Placeholder: `2: /"value_key_to_get"\s*:\s*\[([^\]]*)\]/`

## Troubleshooting

### General Issues
- Make sure you have installed and enabled the plugin.
- If the action does not appear, clear the cache and reload plugins:
  - `php bin/console cache:clear`
  - `php bin/console mautic:plugins:reload`
- If you update the plugin code, repeat the above commands.

### Contact Field Storage
- Only text-type contact fields are available for response storage
- If regex processing fails, the original response body will be stored
- Verify that the selected contact field has sufficient length to store the response data


For complete version history, see [GitHub](https://github.com/Leuchtfeuer/mautic-APICalls-bundle).

## Sponsoring & Commercial Support
For support or custom features, contact **mautic-plugins@leuchtfeuer.com**

## License
This plugin is licensed under the GPL-3.0-or-later License.

## Resources / Further Readings
- [Mautic Documentation](https://docs.mautic.org)
- [Leuchtfeuer Plugins for Mautic](https://leuchtfeuer.com/mautic)

