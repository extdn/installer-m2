# ExtDN Installer for Magento 2 modules

The installation of extensions for Magento 2 has a few scenarios to cover depending on your starting position
(Magento mode being the most influential). A typical installation routine includes 5 or more steps.

The ExtDN installer aims to bring down the installation steps required to just 1 covering a wide range of extension
sources and Magento installs. The installer starts with a range of checks to confirm that the installation is likely
to succeed:

List of checks
* Installation is into a Magento 2 instance
* Files are writable by the current user
* Installation is run by the current user
* No outstanding Composer actions
* Valid project composer.json
* No outstanding Module installation
* Confirmation for installation in production mode instances

## How to use

#### Installation from packagist or Marketplace 
(essentially all previously pre-configured composer repositories)
```
curl -sS https://raw.githubusercontent.com/extdn/installer-m2/master/bin/oneliner.sh | bash -- install fooman/emailattachments-m2
```

#### Installation from Github
```
curl -sS https://raw.githubusercontent.com/extdn/installer-m2/master/bin/oneliner.sh | bash --template=github --repo-url=https://github.com/fooman/emailattachments-m2.git -- install fooman/emailattachments-m2 
```

#### Installation from Fooman Repo
```
curl -sS https://raw.githubusercontent.com/extdn/installer-m2/master/bin/oneliner.sh | bash --template=fooman --repo-url=https://customer-repo.fooman.co.nz/URL-PRIVATE_TOKEN -- install fooman/emailattachments-m2 
```

## Limitations
The installer compares the list of modules before and after installing the extension code to determine which Magento module(s) to enable. This does not work if
the just installed extension was previously installed and is still present in the app/etc/config.php file as disabled. The error in this case would be "No new modules detected."

If installation is performed in production mode and an error is encountered the site will remain in developer mode.