module.exports = class {

	onDataLoad() {
		return {
			userCarList: this.fetchQL('userCarList'),
			empty: 0,
		};
	}

	onLoaded() {
		let len = this.state.userCarList.data.list.length;
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
			url: '/user/clmsg/create',
		})
	}

}