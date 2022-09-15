![Biller logo](https://biller.ai/social.jpg)

# Biller PrestaShop plugin

## Package deployment

- Set proper plugin version in `composer.json` and in `biller.php`
- Run `./deploy.sh` in project's root directory. Command will output results of the deployment operation.
- Write release notes in file located in deployment directory (`PROJECT_ROOT/dist/VERSION_NUMBER`) for current version.

## Plugin validation

- Upload zip archive generated in **Package deployment** step to [the PrestaShop validator tool](https://validator.prestashop.com/).
- Check `PrestaShop 1.7 compliant` and `PrestaShop 1.5 / 1.6 compliant` options.
- Click `Process the validation` button.
