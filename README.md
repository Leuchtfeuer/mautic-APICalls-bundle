# Plugin: Custom Menu Items by Leuchtfeuer

## Overview / Purpose / Features
The Custom Menu Items by Leuchtfeuer plugin allows Mautic administrators to customize the Mautic main navigation menu by adding their own links.
This enables quick access to frequently used external resources (e.g., documentation, guidelines, conventions) directly from the left-hand Mautic menu.

### Key Features:

- Add custom navigation links to Mautic’s sidebar menu.
- Configure link name, order, and URL.
- Manage (create, edit, delete) custom links directly in the plugin configuration.
- Menu items respect configured sort order.
- Uses a default external link icon


## Requirements / Version Support
- Mautic 5.x (minimum 5.2)
- PHP >= 8.0


## Installation
### Composer
```bash
composer require leuchtfeuer/mautic-custommenuitems-bundle
```

### Manual Installation
Alternatively, it can be installed manually, following the usual steps:
- Download the plugin
- Unzip to the Mautic `plugins` directory
- Rename folder to `BundleName`
- In the Mautic backend, go to the `Plugins` page as an administrator
- Click on the `Install/Upgrade Plugins` button to install the Plugin.
  OR
- If you have shell access, execute `php bin\console cache:clear` and `php bin\console mautic:plugins:reload` to install the plugins.

## Configuration

After installation, navigate to Settings → Plugins → Custom Menu Items by Leuchtfeuer and configure your menu items.

- Name (internal identifier)
- Order (sort position in the sidebar)
- URL (link destination)


## Usage

- Once configured, the custom links will appear in the left-hand Mautic navigation menu.
- Clicking a link will either open the target URL in a new tab (blank).
- Menu order reflects the configured sort order.

## Known Issues

- Configuration is only available in the plugin settings.

## Troubleshooting
Make sure you have not only installed but also enabled the Plugin.

If things are still funny, please try

`php bin/console cache:clear`

and

`php bin/console mautic:assets:generate`

## Change log
- https://github.com/Leuchtfeuer/mautic-CustomMenuItems-bundle


## Future Ideas

- iFrame functionality

## Sponsoring & Commercial Support
We are continuously improving our plugins. If you are requiring priority support or custom features, please contact us at **mautic-plugins@leuchtfeuer.com**


## Get Involved
Feel free to open issues or submit pull requests on [GitHub](https://github.com/Leuchtfeuer/mautic-CustomMenuItems-bundle/issues). Follow the contribution guidelines in `CONTRIBUTING.md`.”

## Credits
Developed and maintained by **Leuchtfeuer Digital Marketing GmbH**

## Author

Leuchtfeuer Digital Marketing GmbH

Please raise any issues in GitHub.

For all other things, please email **mautic-plugins@Leuchtfeuer.com**

## License 

This plugin is licensed under the GPL-3.0-or-later License.

## Resources / Further Readings

- [Mautic Documentation](https://docs.mautic.org?utm_source=chatgpt.com)
- [Leuchtfeuer Plugins for Mautic](https://leuchtfeuer.com/mautic/?utm_source=chatgpt.com)

