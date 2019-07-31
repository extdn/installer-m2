# ExtDN Installer for Magento 2 modules

The installation of extensions for Magento 2 has a few scenarios to cover depending on your starting position
(Magento mode being the most influential). A typical installation routine includes 5 or more steps.

The ExtDN installer aims to bring down the installation steps required to just 1 covering a wide range of extension
sources and Magento installs. The installer starts with a range of checks to confirm that the installation is likely
to succeed:

List of checks

## How to use

#### Installation from packagist or Marketplace 
(essentially all previously pre-configured composer repositories)
```
curl -sS https://raw.githubusercontent.com/extdn/installer-m2/master/extdn_installer.php | php -- --package=fooman/emailattachments-m2
```

#### Installation from Github
```
curl -sS https://raw.githubusercontent.com/extdn/installer-m2/master/extdn_installer.php | php -- --package=fooman/emailattachments-m2 --template=github --repo-url=https://github.com/fooman/emailattachments-m2.git
```

#### Installation from Fooman Repo
```
curl -sS https://raw.githubusercontent.com/extdn/installer-m2/master/extdn_installer.php | php -- --package=fooman/emailattachments-m2 --template=fooman --repo-url=https://customer-repo.fooman.co.nz/URL-PRIVATE_TOKEN
```