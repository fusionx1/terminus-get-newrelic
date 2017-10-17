# Terminus Get New Relic

[![Terminus v1.x Compatible](https://img.shields.io/badge/terminus-v1.x-green.svg)](https://github.com/pantheon-systems/terminus)

Terminus Plugin that fetches metric data from the New Relic api:
1. It displays a list of sites without New Relic within an organization
2. It displays slowest performing sites using New Relic data (throughput, response time, Apdex)
3. It shows an alert if the environment is under stress using New Relic color coding
 [Pantheon](https://www.pantheon.io) sites.

Learn more about Terminus and Terminus Plugins at:
[https://pantheon.io/docs/terminus/plugins/](https://pantheon.io/docs/terminus/plugins/)



## Examples

1. Fetches metric data from `dev`
```
terminus newrelic-data:site my_site.dev
```
[![Screenshot](http://dev-wpmanila.pantheonsite.io/wp-content/uploads/nr-site1.png)](https://github.com/pantheon-systems/terminus)

2. Displays all sites without New Relic under an organization by site plan.
```
terminus newrelic-data:org [ORG UUID]
```
[![Screenshot](http://dev-wpmanila.pantheonsite.io/wp-content/uploads/nr-org3.png)](https://github.com/pantheon-systems/terminus)

3. Displays all sites with or without New Relic under an organization by site plan.
```
terminus newrelic-data:org [ORG UUID] --all
```
[![Screenshot](http://dev-wpmanila.pantheonsite.io/wp-content/uploads/nr-org1.png)](https://github.com/pantheon-systems/terminus)

4. Displays all sites with slowest response time  under an organization. It provides an indicator if a site is in normal or in critical condition based on the New Relic health status.
```
terminus newrelic-data:org [ORG UUID] --overview
```
[![Screenshot](http://dev-wpmanila.pantheonsite.io/wp-content/uploads/nr-org2.png)](https://github.com/pantheon-systems/terminus)

## Installation
For help installing, see [Manage Plugins](https://pantheon.io/docs/terminus/plugins/)
```
mkdir -p ~/.terminus/plugins
composer create-project -d ~/.terminus/plugins fusionx1/terminus-get-newrelic:dev-master
composer install
```
## Things to remember
1. If invoking New Relic-data:org make sure you are an administrator of the organisation, otherwise it will only display sites where you are member. 

## Todo
1. To include screenshot of New relic metrics 
2. To add actual PHP Usage
3. To add Browsers time and apdex
