if [ '$TEST_SUITE' = 'marketplaceeqp' ];
then
    composer require --prefer-source --no-interaction squizlabs/php_codesniffer magento/marketplace-eqp
    vendor/squizlabs/php_codesniffer/scripts/phpcs --config-set installed_paths vendor/magento/marketplace-eqp
    vendor/squizlabs/php_codesniffer/scripts/phpcs -n --standard="MEQP2" ./;
fi
