module.exports = class {

	onDataLoad() {
		return {};
	}

	onLoad() {
		padapp.navigate.push({
			url: '/index',
		});

	}

	onLoaded() {
	}
};