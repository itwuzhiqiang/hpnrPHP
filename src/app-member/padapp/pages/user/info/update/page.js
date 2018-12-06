module.exports = class {

	onDataLoad(props) {
		return {
			type: props.type,
			show: false,
			sex: 0,
			val: '',
		};
	}

	changeSex() {
		this.setState({
			show: !this.state.show,
		})
	}

	setSex(sex) {
		this.setState({
			sex: sex,
		});
		this.state.register.setParam('sex', sex);
	}

	goBack() {
		padapp.navigate.pop();
	}

	setVal(e, type) {
		let val = e.target.value;
		this.setState({
			val: val,
		});

		this.state.register.setParam(type, val);
	}

}