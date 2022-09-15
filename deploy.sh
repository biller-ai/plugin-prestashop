#!/bin/bash

echo
echo -e "\e[48;5;124m ALWAYS RUN UNIT TESTS BEFORE CREATING DEPLOYMENT PACKAGE! \e[0m"
echo
sleep 2

# Cleanup any leftovers
echo -e "\e[32mCleaning up...\e[0m"
rm -rf ./biller.zip
rm -rf ./biller

# Create deployment source
echo -e "\e[32mSTEP 1:\e[0m Copying plugin source..."
mkdir biller
cp -r ./src/* biller

# Ensure proper composer dependencies
echo -e "\e[32mSTEP 2:\e[0m Installing composer dependencies..."
cd biller

composer install --no-dev
cd ..

# Remove unnecessary files from final release archive
echo -e "\e[32mSTEP 3:\e[0m Removing unnecessary files from final release archive..."
rm -rf biller/tests
rm -rf biller/config.xml
rm -rf biller/deploy.sh
rm -rf biller/vendor/biller/integration-core/.git
rm -rf biller/vendor/biller/integration-core/.gitignore
rm -rf biller/vendor/biller/integration-core/.idea
rm -rf biller/vendor/biller/integration-core/tests
rm -rf biller/vendor/biller/integration-core/README.md

# Adding PrestaShop mandatory index.php file to all folders
echo -e "\e[32mSTEP 4:\e[0m Adding PrestaShop mandatory index.php file to all folders..."
php "$PWD/lib/autoindex/index.php" "$PWD/biller" > /dev/null

echo -e "\e[32mSTEP 5:\e[0m Adding PrestaShop mandatory licence header to files..."
php "$PWD/lib/autolicence/autoLicence.php" "$PWD/biller"

# get plugin version
echo -e "\e[32mSTEP 6:\e[0m Reading module version..."

version="$1"
if [ "$version" = "" ]; then
    version=$(php -r "echo json_decode(file_get_contents('src/composer.json'), true)['version'];")
    if [ "$version" = "" ]; then
        echo "Please enter new plugin version (leave empty to use root folder as destination) [ENTER]:"
        read version
    else
      echo -e "\e[35mVersion read from the composer.json file: $version\e[0m"
    fi
fi

# Create plugin archive
echo -e "\e[32mSTEP 7:\e[0m Creating new archive..."
zip -r -q  biller.zip ./biller

if [ "$version" != "" ]; then
    if [ ! -d ./dist/ ]; then
        mkdir ./dist/
    fi
    if [ ! -d ./dist/"$version"/ ]; then
        mkdir ./dist/"$version"/
    fi

    mv ./biller.zip ./dist/${version}/
    echo -e "\e[34;5;40mSUCCESS!\e[0m"
    echo -e "\e[93mNew release created under: $PWD/dist/$version"
else
    echo -e "\e[40;5;34mSUCCESS!\e[0m"
    echo -e "\e[93mNew plugin archive created: $PWD/biller.zip"
fi

rm -fR ./biller
