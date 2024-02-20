const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const { getWebpackEntryPoints } = require('@wordpress/scripts/utils/config');
const path = require('path');
const fs = require('fs');

const getDynamicEntryPoints = ( basePath ) => {
	const entryPoints = {};

	const filePath   = path.resolve(__dirname, basePath);
	const folders    = fs.readdirSync(filePath);
	const folderName = path.basename(filePath);

	folders.forEach(folder => {
		const folderPath = path.resolve(filePath, folder);
		
		if (fs.lstatSync(folderPath).isDirectory()) {
			entryPoints[`${folderName}-${folder}`] = `${folderPath}/index.js`;
		}
	})

	return entryPoints;
}

module.exports = {
	...defaultConfig,
	entry: {
		...getWebpackEntryPoints(),
		...getDynamicEntryPoints('./src/admin' ),
	}
}