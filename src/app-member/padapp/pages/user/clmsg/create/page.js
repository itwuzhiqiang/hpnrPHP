module.exports = class {

	onDataLoad() {
		return {
			apiPtText: '请选择',
			ptid: 0,
		};
	}

	onLoad() {
		this.addListener('/user.clmsg.list', (data) => {
			this.setState({
				apiPtText: data.data.instructions,
				ptid: data.data.ptid,
			})
		});
	}

	setVal(type, e) {
		this.state
	}

	goBack() {
		padapp.navigate.pop()
	}

	goList() {
		padapp.navigate.push({
			url: '/user/clmsg/list',
		})
	}

	setVal(e, type) {
		let val = e.target.value;
		this.state.register.setParam(type, val);
	}

};