module.exports = class {

	onDataLoad() {
		return {
			memberLogout: this.dataQL('memberLogout'),
			policeInfo: this.fetchQL('policeInfo'),
		};
	}

	goHistory() {
		window.history.go(-1);
	}

	goUpdate(type) {
		padapp.navigate.push({
			url: '/user/info/update',
			params: {
				type: type,
			}
		});
	}

	goLogout() {
		this.state.memberLogout.fetch((json) => {
			if (json && json.status === 'success') {
				padapp.navigate.linkTo({
					url: '/login'
				})
			}
		})
	}

}