# Terminus Get Newrelic

[![Terminus v1.x Compatible](https://img.shields.io/badge/terminus-v1.x-green.svg)](https://github.com/pantheon-systems/terminus)

Terminus Plugin that fetches metric data from new relic api:
1. It will display overview of newrelic per environment
2. It shows an alert if the environment is under stress using new relic color coding
 [Pantheon](https://www.pantheon.io) sites.

Learn more about Terminus and Terminus Plugins at:
[https://pantheon.io/docs/terminus/plugins/](https://pantheon.io/docs/terminus/plugins/)



## Example

1. Fetches metric data from `dev`
```
terminus newrelic-data my_site.dev
```
[![Screenshot](http://dev-wpmanila.pantheonsite.io/wp-content/uploads/nr-site1.png)](https://github.com/pantheon-systems/terminus)

2. Displays all sites without newrelic under an organization by siteplan order.
```
terminus newrelic-data:org [ORG UUID]
```
[![Screenshot](http://dev-wpmanila.pantheonsite.io/wp-content/uploads/nr-org3.png)](https://github.com/pantheon-systems/terminus)

3. Displays all sites with or without newrelic under an organization by siteplan order.
```
terminus newrelic-data:org [ORG UUID] --all
```
[![Screenshot](http://dev-wpmanila.pantheonsite.io/wp-content/uploads/nr-org1.png)](https://github.com/pantheon-systems/terminus)

4. Displays all sites with slowest response time  under an organization. It provides an indicator if a site is in normal or in critical condition based on newrelic health status.
```
terminus newrelic-data:org [ORG UUID] --overview
```
[![Screenshot](http://dev-wpmanila.pantheonsite.io/wp-content/uploads/nr-org2.png)](https://github.com/pantheon-systems/terminus)

## Installation
For help installing, see [Manage Plugins](https://pantheon.io/docs/terminus/plugins/)
```
mkdir -p ~/.terminus/plugins
composer create-project -d ~/.terminus/plugins fusionx1/terminus-get-newrelic:dev-master
```
## Todo
1. Display overview variation for 1hr, 7days, 3months in one display
2. Add option for transactional, hooks, modules, templates metrics
3. Include error history 
4. Include screenshot of each metrics 
