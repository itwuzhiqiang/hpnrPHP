module.exports = class {

	onDataLoad() {
		return {
			licenseList: this.fetchQL('licenseList'),
			empty: 0,
		};
	}

	onLoad() {
		this.addListener('/user.driver.create', () => {
			this.state.licenseList.fetch();
		})
	}

	onLoaded() {
		let len = this.state.licenseList.data.list.length;
		let empty = this.state.empty;
		if (len > 0) {
			empty = 2;
		} else {
			empty = 1;
		}
		this.setState({
			empty: empty,
		})
	}

	goBack() {
		window.history.go(-1);
	}

	goCreate() {
		padapp.navigate.push({
			url: '/user/driver/create',
		})
	}

}