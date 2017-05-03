# Terminus Get Newrelic

[![Terminus v1.x Compatible](https://img.shields.io/badge/terminus-v1.x-green.svg)](https://github.com/pantheon-systems/terminus)

Terminus Plugin that fetches metric data from new relic api:
1. It will display overview of newrelic per environment
2. It shows an alert if the environment is under stress using new relic color coding
 [Pantheon](https://www.pantheon.io) sites.

Learn more about Terminus and Terminus Plugins at:
[https://pantheon.io/docs/terminus/plugins/](https://pantheon.io/docs/terminus/plugins/)



## Example

Fetches metric data from `dev`.
```
terminus get-newrelic my_site.dev
```
[![Screenshot](http://dev-wpmanila.pantheonsite.io/wp-content/uploads/terminus-get-newrelic2.png)](https://github.com/pantheon-systems/terminus)


## Installation
For help installing, see [Manage Plugins](https://pantheon.io/docs/terminus/plugins/)
```
mkdir -p ~/.terminus/plugins
composer create-project -d ~/.terminus/plugins fusionx1/terminus-get-newrelic:dev-master
```
## Todo
1. Display overview variation for 1hr, 7days, 3months in one display
2. Iclude Appserver response time and throughput, 
3. Add option for transactional, hooks, modules, templates metrics
4. Include error history 
5. Include screenshot of each metrics 
