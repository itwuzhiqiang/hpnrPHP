module.exports = class {

	onDataLoad() {
		return {
			str: 'us1.jpg',
			show: false,
			key: 1,
		};
	}

	onLoaded() {
		this.setState({
			show: true,
		})
	}

	goBack() {
		window.history.go(-1);
	}

	changeKey() {
		let key = this.state.key;
		if (key < 14) {
			this.setState({
				key: key + 1,
			})
		} else {
			this.overlay.msg('已阅读完毕');
		}

	}

}