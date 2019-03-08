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

# Create the package
git archive --format=zip --output=Mollie_Mpm.zip $TRAVIS_COMMIT

git clone https://github.com/magento/marketplace-tools.git

php marketplace-tools/validate_m2_package.php Mollie_Mpm.zip