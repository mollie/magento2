set -e
set -x

if [ "$TEST_SUITE" = "marketplaceeqp" ];
then
    composer global config http-basic.repo.magento.com $MAGENTO_USERNAME $MAGENTO_PASSWORD
    composer config repositories.repo-name composer https://repo.magento.com
    composer require --prefer-source --no-interaction squizlabs/php_codesniffer magento/marketplace-eqp


    echo "vendor"
    ls -al vendor
    echo "vendor/squizlabs"
    ls -al vendor/squizlabs
    echo "vendor/squizlabs/php_codesniffer"
    ls -al vendor/squizlabs/php_codesniffer
    echo "vendor/squizlabs/php_codesniffer/scripts"
    ls -al vendor/squizlabs/php_codesniffer/scripts
    echo "vendor/squizlabs/php_codesniffer/scripts/phpcs"
    ls -al vendor/squizlabs/php_codesniffer/scripts/phpcs


    vendor/squizlabs/php_codesniffer/scripts/phpcs --config-set installed_paths vendor/magento/marketplace-eqp
    vendor/squizlabs/php_codesniffer/scripts/phpcs -n --standard="MEQP2" ./;
fi
