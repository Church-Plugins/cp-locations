# Church Plugins Locations
Church locations plugin for multi location churches.

### Developer info ###
[![Deployment status from DeployBot](https://iwitness-design.deploybot.com/badge/02267418037485/202371.svg)](https://deploybot.com)
##### First-time installation  #####

- Copy or clone the code into `wp-content/plugins/cp-locations/`
- Run these commands
```
composer install
npm install
cd app
npm install
npm run build
```

##### Dev updates  #####

- There is currently no watcher that will update the React app in the WordPress context, so changes are executed through `npm run build` which can be run from either the `cp-locations`

### Change Log

#### 1.0.10.2
* Add support for locations with the same address

#### 1.0.10
* Update assets

#### 1.0.9
* Better handling for multisite

#### 1.0.8
* Add Settings Page
* Disable Location taxonomy by default

#### 1.0.7
* Update Location taxonomy metabox to lookup by slug
* Update core

#### 1.0.6
* Fix single event bug

#### 1.0.5
* Fix recurring event bug

#### 1.0.4
* Fix error thrown when creating new Location

#### 1.0.3
* Include global Locations in Events query

#### 1.0.2
* Include global Locations in all queries

#### 1.0.1
* Fix canonical link creation
* Support The Events Calendar recurring event url structure

#### 1.0.0
* Initial release
