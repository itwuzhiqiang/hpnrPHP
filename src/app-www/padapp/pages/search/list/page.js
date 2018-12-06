module.exports = class {

	onDataLoad(props) {
		return {
			type: props.type ? props.type : 1,
			accidentList: this.fetchQL("accidentSearchList", {
				"type": 1,
				"page": 1,
				"page_size": 20,
			}),
			show: false,
			keywords: '',
			page: 1,
			page_size: 20,
			hasNextPage: false,
			policeInfo: this.fetchQL('policeInfo'),
		};
	}

	getAccidentList(type) {
		// alert(this.state.policeInfo.data.sign);
		this.setState({
			type: type,
		});
		this.state.accidentList
			.setParam('type', type)
			.setParam('page', 1)
			.setParam('page_size', this.state.page_size)
			.setParam('number', this.state.keywords)
			.fetch((json) => {
				// console.log(json.list.length);
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

	setVal(e, type) {
		let val = e.target.value;
		if (type === 'keywords') {
			this.setState({
				keywords: val,
			})
			this.getAccidentList(5)
		}
	}

	goAccidentDetail(rpaid) {
		padapp.navigate.linkTo({
			url: "/search.detail",
			params: {
				rpaid: rpaid,
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

	onLoaded() {
		// console.log('加载数据');
		// console.log(JSON.stringify(this.state.accidentList.data.list));
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

	goToProcess(item) {
		let url = '/handle.remind2';
		let state = item.rpaProcessState;
		let rpaType = item.rpaType;
		let rpaId = item.rpaId;
		if (state == 11) {
			if (rpaType == 1) {
				url = "/handle.shot1";
			} else {
				url = "/handle.shot";
			}
		}
		if (state == 15) {
			url = '/handle.remind2';
		}
		if (state == 32) {
			url = "/handle.information"
		}
		padapp.navigate.linkTo({
			url: url,
			params: {
				rpaId: rpaId,
				type: rpaType,
			}
		})

	}

	goHistory() {
		window.history.go(-1);
	}

}