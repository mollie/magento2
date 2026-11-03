# Sharing Debug Information

This article explains how to download the debug information from your Magento Admin and share it safely, so support can investigate your problem with Mollie Payments for Magento 2.

## Make sure debug logging is enabled

The extension only writes to its log file when **Debug requests** is enabled. Without the log, the debug information is missing the details support needs.

1. Go to **Stores → Configuration**

   ![The Stores menu with Configuration highlighted](../images/debug-information-stores-configuration.png)

2. Open **Mollie → General**

   ![The Mollie tab with General highlighted](../images/debug-information-mollie-general.png)

3. Expand **Debug & Logging** and check that **Debug requests** is set to **Yes**. If the field is greyed out, clear the **Use system value** checkbox first.

   ![The Debug requests setting set to Yes](../images/debug-information-debug-requests.png)

4. Click **Save Config** if you changed the setting

**Important:** If debug logging was disabled, the log does not contain the problem yet. Wait until the problem happens again, or reproduce it, before you download the debug information.

## Download the debug information

The extension collects the logs, configuration, and environment details into a single file.

1. Go to **Stores → Configuration → Mollie → Developer Settings**

   ![The Mollie tab with Developer Settings highlighted](../images/debug-information-developer-settings.png)

2. Expand **Advanced**
3. Click **Download** next to **Download debug information**

   ![The Download debug information button](../images/debug-information-download-button.png)

Your browser downloads a file named like `mollie-debug-20261007-093000.tgz`. Send the file as it is. Do not unpack and repack it.

## What the file contains

The file includes a `README.txt` that describes every file in it, so you can review what you share. In short:

- The last 10,000 lines of `mollie.log`, `system.log`, `exception.log`, and `debug.log`
- The Mollie configuration, with API keys only in their encrypted form
- Your `app/etc/env.php` with the encryption key and all passwords redacted
- The Magento, PHP, and database versions, the installed modules, and `composer.json` and `composer.lock`
- The status of the cache, indexers, and recent cron jobs
- The store URLs and the available payment methods

## Share the file privately

The log files can contain order numbers, customer names, and email addresses. For this reason, do not attach the file to a public GitHub issue.

1. Go to the [Magmodules support form](https://www.magmodules.eu/support)
2. Attach the debug information file
3. Add the link to your GitHub issue, if you have one, and the order numbers that show the problem

## Next Steps

- [Troubleshooting](TROUBLESHOOTING.md): Built-in diagnostic tools and common problems
- [Configuration](CONFIGURATION.md): All general settings explained
