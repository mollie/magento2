set -e
set -x

if [ "$TEST_SUITE" = "marketplaceeqp" ];
then
    composer global config http-basic.repo.magento.com $MAGENTO_USERNAME $MAGENTO_PASSWORD
    composer config repositories.repo-magento-com composer https://repo.magento.com
    composer require --no-interaction magento/marketplace-eqp

    vendor/bin/phpcs --config-set installed_paths vendor/magento/marketplace-eqp
    vendor/bin/phpcs -p --ignore=*/vendor/*,*/Tests/* -n --severity=9 --standard="MEQP2" ./
    vendor/bin/phpcs -p -n --severity=9 --standard="MEQP2" ./Tests/
fi
