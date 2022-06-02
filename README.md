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

#### 1.0.0
* Initial release
