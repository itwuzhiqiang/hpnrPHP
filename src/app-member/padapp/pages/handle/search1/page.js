module.exports = class {

	onDataLoad() {
		return {
			accidentList: this.fetchQL("accidentSearchList", {
				"type": 2,
				"page": 1,
				"page_size": 20,
			}),
			page_size: 20,
			show: false,
			hasNextPage: false,
			type: 2,
			userInfo: this.fetchQL('userInfo'),
		};
	}

	onLoaded() {
		let code = this.state.userInfo.data.code;
		if (code === -1) {
			this.overlay.msg('登录失效');
			padapp.navigate.linkTo({
				url: '/login',
				params: {
					popup: true,
				}
			})
		}

		if (this.state.accidentList.data.list.length == 0) {
			this.setState({
				show: true,
			});
		}
		if (this.state.accidentList.data.list.length >= this.state.page_size) {
			this.setState({
				hasNextPage: true,
			});
		}
	}

	getAccidentList(type) {
		this.setState({
			type: type,
		});
		this.state.accidentList
			.setParam('type', type)
			.setParam('page', 1)
			.setParam('page_size', 5)
			.fetch((json) => {
				if (json.list.length == 0) {
					this.setState({
						show: true,
					});
				} else {
					this.setState({
						show: false,
					});
				}
			});
	}

	nextPage() {
		let nextpage = this.state.page + 1
		this.setState({
			page: nextpage,
		});
		this.state.accidentList
			.setParam('type', this.state.type)
			.setParam('page', nextpage)
			.setParam('page_size', this.state.page_size)
			.setParam('number', this.state.keywords)
			.fetch((json) => {
				if (json.list.length == 0) {
					this.setState({
						show: true,
					});
				}
				if (json.list.length >= this.state.page_size) {
					this.setState({
						hasNextPage: true,
					});
				} else {
					this.setState({
						hasNextPage: false,
					});
				}
			});
	}

	goDetail(item) {
		let rpaId = item.rpaId;
		let rpaType = item.rpaType;
		let rpaProcessState = item.rpaProcessState;
		let url = '';
		if (this.state.type === 2) {
			if (rpaProcessState === 11) {
				url = '/handle.shot1';
			}
			if (rpaProcessState >= 13 && rpaProcessState <= 16) {
				url = '/handle.shot4';
			}

			if (rpaProcessState === 21) {
				url = '/handle.duty';
			}

			if (rpaProcessState >= 31 && rpaProcessState <= 33) {
				url = '/handle.shot5';
			}

			padapp.navigate.linkTo({
				url: url,
				params: {
					rpaId: rpaId,
					type: rpaType,
				}
			})
		} else {
			padapp.navigate.linkTo({
				url: '/handle.search1.detail',
				params: {
					rpaId: rpaId,
				}
			})
		}
	}

	goUser() {
		padapp.navigate.linkTo({
			url: '/user.index',
		})
	}

	goHome() {
		padapp.navigate.linkTo({
			url: '/home',
		})
	}

	goBack() {
		window.history.go(-1);
	}

}