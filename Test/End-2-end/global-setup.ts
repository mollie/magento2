/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

import {type FullConfig} from '@playwright/test';

async function globalSetup(config: FullConfig) {
  process.env['NODE_TLS_REJECT_UNAUTHORIZED'] = '0';
  const { baseURL } = config.projects[0].use;

  if (process.env.NO_API_KEY_TEST === 'true') {
      return;
  }

  await Promise.all([
      getAvailableMethods(baseURL),
      getAdminToken(baseURL),
  ]);
}

const getAvailableMethods = async (baseURL: string) => {
  const urlObj = new URL(baseURL);
  const hostname = urlObj.hostname;

  const query = `
    query {
       molliePaymentMethods(input:{amount:50}) {
         methods {
           code
           image
           name
         }
       }
      }
    `;

  const options = {
    'method': 'GET',
    'hostname': hostname,
    'path': '/graphql?query=' + encodeURIComponent(query),
    'headers': {
      'Content-Type': 'application/json',
    },
    'maxRedirects': 20
  };

  console.log('Requesting Mollie payment methods from "' + baseURL + '". One moment please...');

  let data, rawText;
  let response = await fetch(baseURL + options.path, options);
  try {
      rawText = await response.text();
      data = JSON.parse(rawText);

      if (data.data.molliePaymentMethods === null) {
          throw new Error('Incorrect data received: ' + JSON.stringify(data));
      }
  } catch (error) {
      console.error('Error parsing response from Mollie payment methods:', error);
      console.info('Response text:', rawText);
      throw 'Error parsing response from Mollie payment methods:' + error;
  }

  const methods = data.data.molliePaymentMethods.methods.map(data => {
    return data.code
  })

  process.env.mollie_available_methods = methods;

  console.log('Available Mollie payment methods: ', methods);
}

const getAdminToken = async (baseURL: string) => {
  const urlObj = new URL(baseURL);
  const hostname = urlObj.hostname;

  const username = 'exampleuser';
  const password = 'examplepassword123';

  const options = {
    'method': 'POST',
    'hostname': hostname,
    'path': '/rest/all/V1/integration/admin/token',
    'headers': {
      'accept': 'application/json',
      'Content-Type': 'application/json',
    },
    'body': JSON.stringify({
      'username': username,
      'password': password,
    }),
  };

  console.log('Requesting admin token from "' + baseURL + '". One moment please...');

  let response = await fetch(baseURL + options.path, options);
  process.env.admin_token = await response.json();
}

export default globalSetup;
