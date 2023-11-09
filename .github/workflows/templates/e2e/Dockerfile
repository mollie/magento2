FROM cypress/included:12.1.0

WORKDIR /e2e

RUN apt-get update && apt-get install -y curl && rm -rf /var/lib/apt/lists/*
RUN npm i @cypress/webpack-preprocessor cypress-mollie cypress-testrail --save-dev

CMD tail -f /dev/null
ENTRYPOINT tail -f /dev/null
