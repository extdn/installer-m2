# ExtDN Installer for Magento 2 modules

[![Build Status](https://travis-ci.org/extdn/installer-m2.svg?branch=master)](https://travis-ci.org/extdn/installer-m2)

![Installer in Action](docs/ProductionModeInstall.gif?raw=true")

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

## Should I use this?
Do you have an existing deployment or development process for M2 extension installations? If yes you likely do not benefit from this installer, if no please read on.

## How to use

### Option 1 - Oneliner
This option is likely provided by an ExtDN member and allows the installation of your extension via just 1 command. Below are some examples for this.

#### Oneliner Installation from packagist or Marketplace 
(essentially all previously pre-configured composer repositories)
```
sh -ic "$(curl -sS https://raw.githubusercontent.com/extdn/installer-m2/master/bin/oneliner.sh)" -- install fooman/emailattachments-m2:^3.0
```

#### Oneliner Installation from Github
```
sh -ic "$(curl -sS https://raw.githubusercontent.com/extdn/installer-m2/master/bin/oneliner.sh)"  -- --template=github --repo-url=https://github.com/fooman/emailattachments-m2.git install fooman/emailattachments-m2:^3.0 
```

#### Oneliner Installation from Fooman Repo
```
sh -ic "$(curl -sS https://raw.githubusercontent.com/extdn/installer-m2/master/bin/oneliner.sh)"  -- --template=fooman --repo-url=https://customer-repo.fooman.co.nz/URL-PRIVATE_TOKEN install fooman/emailattachments-m2:^3.0 
```

### Option 2 - Phar
Download and save the [ExtDN_Installer](https://github.com/extdn/installer-m2/raw/v1.0.0-rc3/build/extdn_installer.phar). Ensure it is executable with `chmod +x extdn_installer.phar`.

#### Phar Installation from packagist or Marketplace 
(essentially all previously pre-configured composer repositories)
```
./extdn_installer.phar -- install fooman/emailattachments-m2:^3.0
```

#### Phar Installation from Github
```
./extdn_installer.phar --template=github --repo-url=https://github.com/fooman/emailattachments-m2.git -- install fooman/emailattachments-m2:^3.0 
```

#### Phar Installation from Fooman Repo
```
./extdn_installer.phar --template=fooman --repo-url=https://customer-repo.fooman.co.nz/URL-PRIVATE_TOKEN -- install fooman/emailattachments-m2:^3.0 
```

## Vendor agnostic
The installer is open to a wide range of sources to install from and is not limited to any specific vendor (nor is ExtDN membership needed). Your package could be available via packagist.org, 
Marketplace (repo.magento.com) or Github. Further it will work for any other vendor that supplies their packages via a composer repository url. Simply supply your vendor name in the `--template` argument 
and provide the repository url via `--repo-url`. If your installation is not covered feel free to open a PR that provides a new template.

## Limitations
The installer compares the list of modules before and after installing the extension code to determine which Magento module(s) to enable. This does not work if
the just installed extension was previously installed and is still present in the app/etc/config.php file as disabled. The error in this case would be "No new modules detected."

If installation is performed in production mode and an error is encountered the site will remain in developer mode.

No Windows support - see #5