set -e
set -x

if [ "$UNIT_TEST" = "true" ];
then
    composer global config http-basic.repo.magento.com $MAGENTO_USERNAME $MAGENTO_PASSWORD
    composer config repositories.repo-magento-com composer https://repo.magento.com
    composer install

    vendor/bin/phpunit
fi

if [ "$MEQP_CHECK" = "true" ];
then
    if [ -z "$MEQP_LEVEL" ];
    then
        MEQP_LEVEL=9
    fi

    composer global config http-basic.repo.magento.com $MAGENTO_USERNAME $MAGENTO_PASSWORD
    composer config repositories.repo-magento-com composer https://repo.magento.com
    composer require --no-interaction magento/marketplace-eqp

    vendor/bin/phpcs --config-set installed_paths vendor/magento/marketplace-eqp
    vendor/bin/phpcs -p --ignore=*/vendor/*,*/Tests/* -n --severity=$MEQP_LEVEL --standard="MEQP2" ./
    vendor/bin/phpcs -p -n --severity=9 --standard="MEQP2" ./Tests/
fi

if [ "$TOOLS_CHECK" = "true" ];
then
    FILENAME="Mollie_Payment.zip"

    if [ ! -z "$TRAVIS_TAG" ];
    then
        echo $TRAVIS_TAG;
        FILENAME="Mollie_Payment-${TRAVIS_TAG:1}.zip"
    fi

    # Create the package
    git archive --format=zip --output=$FILENAME $TRAVIS_COMMIT

    git clone https://github.com/magento/marketplace-tools.git

    php marketplace-tools/validate_m2_package.php -d $FILENAME
fi
