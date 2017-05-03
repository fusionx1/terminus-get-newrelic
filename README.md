# Terminus Get Newrelic

[![Terminus v1.x Compatible](https://img.shields.io/badge/terminus-v1.x-green.svg)](https://github.com/pantheon-systems/terminus-secrets-plugin/tree/1.x)

Terminus Plugin that fetches metric data from new relic api:
1. It will display overview of newrelic per environment
2. It shows an alert if the environment is under stress using new relic color coding
 [Pantheon](https://www.pantheon.io) sites.

Learn more about Terminus and Terminus Plugins at:
[https://pantheon.io/docs/terminus/plugins/](https://pantheon.io/docs/terminus/plugins/)

## Configuration

This plugin requires no configuration to use.

## Example

Fetches metric data from `dev`.
```
terminus getNewrelic my_site.dev
```


## Installation
For help installing, see [Manage Plugins](https://pantheon.io/docs/terminus/plugins/)
```
mkdir -p ~/.terminus/plugins
cd ~/.terminus/plugins
git clone https://github.com/fusionx1/terminus-get-newrelic.git
```
