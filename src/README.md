# Installing Salesforce CLI

Use [this url](https://developer.salesforce.com/docs/atlas.en-us.sfdx_setup.meta/sfdx_setup/sfdx_setup_install_cli.htm#sfdx_setup_install_cli_macos) to install the CLI on your machine.

# Auth using CLI

```bash
sf org login web --instance-url https://custom-domain.sandbox.my.salesforce.com/
```

# Obtain access token
```bash
sf org display --target-org <username>
```