module.exports = class {

	onDataLoad() {
		return {
			// getHistory: this.fetchQL('getHistory')
		}
	}

	onLoaded() {
	}

	goLogin() {
		padapp.navigate.linkTo({
			url: '/login'
		})
	}
}