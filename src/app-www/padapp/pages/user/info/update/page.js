module.exports = class {

	onDataLoad(props) {
		return {
			type: props.type,
			title: '',
		};
	}

	onLoaded() {
		let type = this.state.type;
		let title = this.state.title;
		if (type === 'name') {
			title = '姓名';
		}
		if (type === 'sex') {
			title = '性别';
		}
		if (type === 'phone') {
			title = '手机号码';
		}
		if (type === 'number') {
			title = '警员编号';
		}
		if (type === 'address') {
			title = '所属警区';
		}
		this.setState({
			title: title,
		})
	}

	goSure() {
		padapp.navigate.pop()
	}

	goBack() {
		padapp.navigate.pop()
	}

}