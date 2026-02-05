# Plugin: API Calls by Leuchtfeuer

## Overview / Purpose / Features
This feature introduces a new campaign action in Mautic that allows campaigns to send outbound API requests.

## Requirements for this release
> [!TIP]
> Other releases of this plugin may cover different Mautic versions!
- Mautic 6

### Key Features:
- **Campaign action**: Send API request with comprehensive configuration options

- **HTTP methods**: Choose between GET, POST, PUT, or PATCH

- **Custom request body**: Define raw request body with token replacement support

- **Authentication options**:
  - Basic HTTP authentication (username/password)
  - Custom authorization headers (Bearer tokens, API keys, etc.)

- **Response tracking**: Capture raw response headers and body and make them visible in the contact's timeline history

- **Contact field storage**: Store response data in selected contact fields with regex or JSON parsing

- **GET request parameters**: Support for URL parameters when using GET method

- **Token replacement**: Use contact field tokens in URLs and request bodies

- **Redirect handling**: Automatic handling of HTTP redirects 

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
- In the Campaign Builder, add the "Send API Request" action to your campaign.
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

### Authentication Options
Configure authentication for your API requests:

#### Basic Authentication
- **Username**: Enter the username for basic HTTP authentication
- **Password**: Enter the password for basic HTTP authentication

#### Custom Authorization Headers
Add custom authorization headers for API authentication:
- **Additional HTTP Header line**: Add any custom header (e.g., `Authorization: Bearer eyJhbGc`)
- The header is sent exactly as entered with the request
- Common example:
  - `Authorization: Bearer eyJhbGc`

### Contact Field Storage & Response Processing
Store and process API response data in contact fields with two powerful options:

#### 1. JSON Response Parser (Recommended)
Extract specific values from JSON responses using intuitive dot notation:
- **Object Key**: Specify the parent object to search within (optional)
- **Value Key**: Use dot notation to extract the desired value

**JSON Parser Examples:**
```json
{
  "user": {
    "email": "john@example.com",
    "profile": {
      "name": "John Doe",
      "age": 30
    }
  },
  "items": [
    {"name": "Product A", "price": 19.99},
    {"name": "Product B", "price": 29.99}
  ]
}
```

- To get `john@example.com`: Object Key: `user`, Value Key: `email`
- To get `John Doe`: Object Key: `user`, Value Key: `profile.name`
- To get first item name: Value Key: `items[0].name`
- To get user's age: Object Key: `user`, Value Key: `profile.age`

#### 2. Regex Processing (Advanced)
Apply regular expressions to extract specific data from any response format:
- Use standard regex patterns to filter or transform response data
- Leave empty to store the entire response body

**Regex Examples:**
- Extract email: `/[\w\.-]+@[\w\.-]+\.\w+/`
- Extract numbers: `/\d+/`
- Extract JSON value: `/"email"\s*:\s*"([^"]+)"/`

## Troubleshooting

### General Issues
- Make sure you have installed and enabled the plugin.
- If the action does not appear, clear the cache and reload plugins:
  - `php bin/console cache:clear`
  - `php bin/console mautic:plugins:reload`
- If you update the plugin code, repeat the above commands.

### Contact Field Storage & Response Processing
- Only text-type contact fields are available for response storage
- **JSON Parser**: If the response is valid JSON and keys are found, the extracted value is stored
- **Regex Processing**: If regex processing fails, the original response body will be stored
- Verify that the selected contact field has sufficient length to store the response data

### Authentication Issues
- Verify that your authorization header format is correct (e.g., `Authorization: Bearer eyJhbGc`)
- Ensure that custom headers contain a colon (`:`) to separate the header name from the value
- Basic authentication and custom headers can be used together if needed


For complete version history, see [GitHub](https://github.com/Leuchtfeuer/mautic-APICalls-bundle).

## Sponsoring & Commercial Support
For support or custom features, contact **mautic-plugins@leuchtfeuer.com**

## License
This plugin is licensed under the GPL-3.0-or-later License.

## Resources / Further Readings
- [Mautic Documentation](https://docs.mautic.org)
- [Leuchtfeuer Plugins for Mautic](https://leuchtfeuer.com/mautic)

